<?php

namespace Sunchayn\Nimbus\Tests\App\Modules\Relay\Actions;

use Carbon\CarbonImmutable;
use Illuminate\Cookie\CookieValuePrefix;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use Ramsey\Uuid\Uuid;
use Sunchayn\Nimbus\Modules\Relay\Actions\RequestRelayAction;
use Sunchayn\Nimbus\Modules\Relay\Authorization\AuthorizationCredentials;
use Sunchayn\Nimbus\Modules\Relay\Authorization\AuthorizationTypeEnum;
use Sunchayn\Nimbus\Modules\Relay\Authorization\Handlers\AuthorizationHandler;
use Sunchayn\Nimbus\Modules\Relay\Authorization\Handlers\AuthorizationHandlerFactory;
use Sunchayn\Nimbus\Modules\Relay\DataTransferObjects\RelayedRequestResponseData;
use Sunchayn\Nimbus\Modules\Relay\DataTransferObjects\RequestRelayData;
use Sunchayn\Nimbus\Modules\Relay\Parsers\VarDumpParser\DataTransferObjects\ParseResultDto;
use Sunchayn\Nimbus\Modules\Relay\Parsers\VarDumpParser\VarDumpParser;
use Sunchayn\Nimbus\Modules\Relay\Responses\DieAndDumpResponse;
use Sunchayn\Nimbus\Modules\Relay\ValueObjects\ResponseCookieValueObject;
use Sunchayn\Nimbus\Tests\TestCase;
use Symfony\Component\HttpFoundation\ParameterBag;

#[CoversClass(RequestRelayAction::class)]
#[CoversClass(RequestRelayData::class)]
#[CoversClass(RelayedRequestResponseData::class)]
#[CoversClass(DieAndDumpResponse::class)]
class RequestRelayActionFunctionalTest extends TestCase
{
    private const ENDPOINT = 'https://localhost/api/test-endpoint';

    #[TestWith([200, 'OK'])]
    #[TestWith([404, 'Not Found'])]
    #[TestWith([301, 'Moved Permanently'])]
    #[TestWith([500, 'Internal Server Error'])]
    #[TestWith([419, 'Method Not Allowed'])]
    public function test_it_relays_requests(
        int $stubStatusCode,
        string $expectedStatusText,
    ): void {
        // Arrange

        $requestData = new RequestRelayData(
            method: Arr::random([
                'POST',
                'GET',
                'PUT',
            ]),
            endpoint: self::ENDPOINT,
            authorization: $authorizationCredentials = $this->getRandomAuthorizationCredentials(),
            headers: [
                'Content-Type' => fake()->mimeType(),
                'X-Custom-Header' => $customHeaderValue = uniqid(),
            ],
            body: ['test' => 'data'],
            cookies: new ParameterBag,
        );

        CarbonImmutable::setTestNow(CarbonImmutable::now());

        $stubBody = ['message' => 'success', 'data' => ['test' => 'data']];

        $encryptedCookieOriginalValue = 'abc123xyzEncrypted';
        $encryptedCookieValue = $this->getEncryptedCookieValue('sessionIdEncrypted', $encryptedCookieOriginalValue);

        $stubHeaders = [
            'Set-Cookie' => [
                'sessionId=abc123xyz; Path=/; HttpOnly; Secure; SameSite=Strict; Expires=Mon, 30 Sep 2025 23:59:59 GMT',
                "sessionIdEncrypted={$encryptedCookieValue}; Path=/; HttpOnly; Secure; SameSite=Strict; Expires=Mon, 30 Sep 2025 23:59:59 GMT",
            ],
        ];

        // Anticipate

        Http::fake(function (Request $request) use ($stubStatusCode, $stubBody, $stubHeaders) {
            if ($request->url() !== self::ENDPOINT) {
                return null;
            }

            return Http::response(
                body: $stubBody + ['requestHeaders' => $request->headers()],
                status: $stubStatusCode,
                headers: $stubHeaders,
            );
        });

        $dummyAuthorizationHandler = $this->mock(
            AuthorizationHandler::class,
            fn (MockInterface $mock) => $mock->shouldReceive('authorize')
                ->with(Mockery::type(PendingRequest::class))
                ->andReturnArg(index: 0),
        );

        $this->mock(
            AuthorizationHandlerFactory::class,
            fn (MockInterface $mock) => $mock
                ->shouldReceive('create')
                ->with($authorizationCredentials)
                ->andReturn($dummyAuthorizationHandler),
        );

        $requestRelayAction = resolve(RequestRelayAction::class);

        // Act

        $response = $requestRelayAction->execute($requestData);

        // Assert

        $this->assertEquals($stubStatusCode, $response->statusCode);

        $this->assertEquals($expectedStatusText, $response->statusText);

        $this->assertEquals(
            $stubBody,
            Arr::except(
                $response->body->body,
                'requestHeaders',
            ),
        );

        $this->assertEquals(
            $customHeaderValue,
            $response->body->body['requestHeaders']['X-Custom-Header'][0] ?? -1,
        );

        $this->assertEquals(
            CarbonImmutable::now()->timestamp,
            $response->timestamp,
        );

        $this->assertRelayResponseCookies(
            expectedCookies: [
                [
                    'name' => 'sessionId',
                    'raw' => 'abc123xyz',
                    'decrypted' => null,
                ],
                [
                    'name' => 'sessionIdEncrypted',
                    'raw' => $encryptedCookieValue,
                    'decrypted' => $encryptedCookieOriginalValue,
                ],
            ],
            actualCookies: $response->cookies,
        );

        $this->assertEquals(
            [
                'Content-Type' => ['application/json'],
                ...$stubHeaders,
            ],
            $response->headers,
        );

        $this->assertEqualsWithDelta(
            5,
            $response->durationMs,
            delta: 50, // <- Sweet spot for fluctuation.
        );
    }

    #[TestWith(['get'])]
    #[TestWith(['head'])]
    public function test_it_merges_body_into_query_parameters_for_get_and_head_requests(string $method): void
    {
        // Arrange

        $bodyData = ['filter' => 'active', 'sort' => 'desc'];

        $queryParameters = ['page' => '1', 'limit' => '10'];

        $requestData = new RequestRelayData(
            method: $method,
            endpoint: self::ENDPOINT,
            authorization: AuthorizationCredentials::none(),
            headers: ['X-Custom-Header' => 'test'],
            body: $bodyData,
            cookies: new ParameterBag,
            queryParameters: $queryParameters,
        );

        // Anticipate

        Http::fake(function (Request $request) use ($bodyData, $queryParameters) {
            // Assert that body data is merged into query parameters
            $expectedQueryParams = array_merge($queryParameters, $bodyData);

            foreach ($expectedQueryParams as $key => $value) {
                if (! str_contains($request->url(), "{$key}={$value}")) {
                    return Http::response(['error' => 'Missing query parameter'], 400);
                }
            }

            // Assert that the request body is empty
            if (! empty($request->body())) {
                return Http::response(['error' => 'Body should be empty'], 400);
            }

            return Http::response([
                'success' => true,
            ]);
        });

        $this->mockAuthorizationHandler();

        $requestRelayAction = resolve(RequestRelayAction::class);

        // Act

        $response = $requestRelayAction->execute($requestData);

        // Assert

        $this->assertEquals(200, $response->statusCode);

        $this->assertEquals(
            [
                'success' => true,
            ],
            $response->body->body,
        );
    }

    public function test_it_sends_json_body_by_default(): void
    {
        // Arrange

        $bodyData = ['user' => 'john', 'action' => 'login'];

        $requestData = new RequestRelayData(
            method: 'post',
            endpoint: self::ENDPOINT,
            authorization: AuthorizationCredentials::none(),
            headers: [], // <- Content-Type is missing => Json by default.
            body: $bodyData,
            cookies: new ParameterBag,
        );

        // Anticipate

        Http::fake(function (Request $request) use ($bodyData) {
            $requestBody = json_decode($request->body(), true);

            return Http::response([
                'receivedBody' => $requestBody,
                'bodyMatches' => $requestBody === $bodyData,
            ], 200);
        });

        $this->mockAuthorizationHandler();

        $requestRelayAction = resolve(RequestRelayAction::class);

        // Act

        $response = $requestRelayAction->execute($requestData);

        // Assert

        $this->assertTrue($response->body->body['bodyMatches'], 'POST body should be sent as JSON');

        $this->assertEquals($bodyData, $response->body->body['receivedBody']);
    }

    public function test_it_url_decodes_cookie_values(): void
    {
        // Arrange

        $requestData = new RequestRelayData(
            method: 'get',
            endpoint: self::ENDPOINT,
            authorization: AuthorizationCredentials::none(),
            headers: [],
            body: [],
            cookies: new ParameterBag,
        );

        $encodedValue = urlencode('test value with spaces');

        $stubHeaders = [
            'Set-Cookie' => [
                "testCookie={$encodedValue}; Path=/; HttpOnly",
            ],
        ];

        // Anticipate

        Http::fake(fn () => Http::response(['success' => true], 200, $stubHeaders));

        $this->mockAuthorizationHandler();

        $requestRelayAction = resolve(RequestRelayAction::class);

        // Act

        $response = $requestRelayAction->execute($requestData);

        // Assert

        $this->assertCount(1, $response->cookies);

        $this->assertEquals('test value with spaces', $response->cookies[0]->toArray()['value']['raw']);
    }

    public function test_it_parses_die_and_dump_responses(): void
    {
        // Arrange

        $this->freezeTime();

        $requestData = new RequestRelayData(
            method: 'GET',
            endpoint: self::ENDPOINT,
            authorization: $authorizationCredentials = $this->getRandomAuthorizationCredentials(),
            headers: [
                'Content-Type' => 'application/json',
                'X-Custom-Header' => $customHeaderValue = uniqid(),
            ],
            body: ['test' => 'data'],
            cookies: new ParameterBag,
        );

        $uuid = fake()->uuid;
        $stubSource = fake()->filePath();
        $stubDumps = [
            'type' => fake()->word(),
            'value' => fake()->word(),
        ];

        $parseResultDtoMock = Mockery::mock(ParseResultDto::class);
        $varDumpParserMock = $this->mock(VarDumpParser::class);

        $dumpHtml = <<<'HTML'
<script> Sfdump = window.Sfdump || (function (doc) { doc.documentElement.classList.add('sf-js-enabled'); var rxEsc = /([.*+?^${}()|\[\]\/\\])/g, idRx = /\bsf-dump-\d+-ref[012]\w+\b/, keyHint = 0 <= navigator.platform.toUpperCase().indexOf('MAC') ? 'Cmd' : 'Ctrl', addEventListener = function (e, n, cb) { e.addEventListener(n, cb, false); }; if (!doc.addEventListener) { addEventListener = function (element, eventName, callback) { element.attachEvent('on' + eventName, function (e) { e.preventDefault = function () {e.returnValue = false;}; e.target = e.srcElement; callback(e); }); }; } function toggle(a, recursive) { var s = a.nextSibling || {}, oldClass = s.className, arrow, newClass; if (/\bsf-dump-compact\b/.test(oldClass)) { arrow = '&#9660;'; newClass = 'sf-dump-expanded'; } else if (/\bsf-dump-expanded\b/.test(oldClass)) { arrow = '&#9654;'; newClass = 'sf-dump-compact'; } else { return false; } if (doc.createEvent && s.dispatchEvent) { var event = doc.createEvent('Event'); event.initEvent('sf-dump-expanded' === newClass ? 'sfbeforedumpexpand' : 'sfbeforedumpcollapse', true, false); s.dispatchEvent(event); } a.lastChild.innerHTML = arrow; s.className = s.className.replace(/\bsf-dump-(compact|expanded)\b/, newClass); if (recursive) { try { a = s.querySelectorAll('.'+oldClass); for (s = 0; s < a.length; ++s) { if (-1 == a[s].className.indexOf(newClass)) { a[s].className = newClass; a[s].previousSibling.lastChild.innerHTML = arrow; } } } catch (e) { } } return true; }; function collapse(a, recursive) { var s = a.nextSibling || {}, oldClass = s.className; if (/\bsf-dump-expanded\b/.test(oldClass)) { toggle(a, recursive); return true; } return false; }; function expand(a, recursive) { var s = a.nextSibling || {}, oldClass = s.className; if (/\bsf-dump-compact\b/.test(oldClass)) { toggle(a, recursive); return true; } return false; }; function collapseAll(root) { var a = root.querySelector('a.sf-dump-toggle'); if (a) { collapse(a, true); expand(a); return true; } return false; } function reveal(node) { var previous, parents = []; while ((node = node.parentNode || {}) && (previous = node.previousSibling) && 'A' === previous.tagName) { parents.push(previous); } if (0 !== parents.length) { parents.forEach(function (parent) { expand(parent); }); return true; } return false; } function highlight(root, activeNode, nodes) { resetHighlightedNodes(root); Array.from(nodes||[]).forEach(function (node) { if (!/\bsf-dump-highlight\b/.test(node.className)) { node.className = node.className + ' sf-dump-highlight'; } }); if (!/\bsf-dump-highlight-active\b/.test(activeNode.className)) { activeNode.className = activeNode.className + ' sf-dump-highlight-active'; } } function resetHighlightedNodes(root) { Array.from(root.querySelectorAll('.sf-dump-str, .sf-dump-key, .sf-dump-public, .sf-dump-protected, .sf-dump-private')).forEach(function (strNode) { strNode.className = strNode.className.replace(/\bsf-dump-highlight\b/, ''); strNode.className = strNode.className.replace(/\bsf-dump-highlight-active\b/, ''); }); } return function (root, x) { root = doc.getElementById(root); var indentRx = new RegExp('^('+(root.getAttribute('data-indent-pad') || ' ').replace(rxEsc, '\\$1')+')+', 'm'), options = {"maxDepth":1,"maxStringLength":160,"fileLinkFormat":false}, elt = root.getElementsByTagName('A'), len = elt.length, i = 0, s, h, t = []; while (i < len) t.push(elt[i++]); for (i in x) { options[i] = x[i]; } function a(e, f) { addEventListener(root, e, function (e, n) { if ('A' == e.target.tagName) { f(e.target, e); } else if ('A' == e.target.parentNode.tagName) { f(e.target.parentNode, e); } else { n = /\bsf-dump-ellipsis\b/.test(e.target.className) ? e.target.parentNode : e.target; if ((n = n.nextElementSibling) && 'A' == n.tagName) { if (!/\bsf-dump-toggle\b/.test(n.className)) { n = n.nextElementSibling || n; } f(n, e, true); } } }); }; function isCtrlKey(e) { return e.ctrlKey || e.metaKey; } function xpathString(str) { var parts = str.match(/[^'"]+|['"]/g).map(function (part) { if ("'" == part) { return '"\'"'; } if ('"' == part) { return "'\"'"; } return "'" + part + "'"; }); return "concat(" + parts.join(",") + ", '')"; } function xpathHasClass(className) { return "contains(concat(' ', normalize-space(@class), ' '), ' " + className +" ')"; } a('mouseover', function (a, e, c) { if (c) { e.target.style.cursor = "pointer"; } }); a('click', function (a, e, c) { if (/\bsf-dump-toggle\b/.test(a.className)) { e.preventDefault(); if (!toggle(a, isCtrlKey(e))) { var r = doc.getElementById(a.getAttribute('href').slice(1)), s = r.previousSibling, f = r.parentNode, t = a.parentNode; t.replaceChild(r, a); f.replaceChild(a, s); t.insertBefore(s, r); f = f.firstChild.nodeValue.match(indentRx); t = t.firstChild.nodeValue.match(indentRx); if (f && t && f[0] !== t[0]) { r.innerHTML = r.innerHTML.replace(new RegExp('^'+f[0].replace(rxEsc, '\\$1'), 'mg'), t[0]); } if (/\bsf-dump-compact\b/.test(r.className)) { toggle(s, isCtrlKey(e)); } } if (c) { } else if (doc.getSelection) { try { doc.getSelection().removeAllRanges(); } catch (e) { doc.getSelection().empty(); } } else { doc.selection.empty(); } } else if (/\bsf-dump-str-toggle\b/.test(a.className)) { e.preventDefault(); e = a.parentNode.parentNode; e.className = e.className.replace(/\bsf-dump-str-(expand|collapse)\b/, a.parentNode.className); } }); elt = root.getElementsByTagName('SAMP'); len = elt.length; i = 0; while (i < len) t.push(elt[i++]); len = t.length; for (i = 0; i < len; ++i) { elt = t[i]; if ('SAMP' == elt.tagName) { a = elt.previousSibling || {}; if ('A' != a.tagName) { a = doc.createElement('A'); a.className = 'sf-dump-ref'; elt.parentNode.insertBefore(a, elt); } else { a.innerHTML += ' '; } a.title = (a.title ? a.title+'\n[' : '[')+keyHint+'+click] Expand all children'; a.innerHTML += elt.className == 'sf-dump-compact' ? '<span>&#9654;</span>' : '<span>&#9660;</span>'; a.className += ' sf-dump-toggle'; x = 1; if ('sf-dump' != elt.parentNode.className) { x += elt.parentNode.getAttribute('data-depth')/1; } } else if (/\bsf-dump-ref\b/.test(elt.className) && (a = elt.getAttribute('href'))) { a = a.slice(1); elt.className += ' sf-dump-hover'; elt.className += ' '+a; if (/[\[{]$/.test(elt.previousSibling.nodeValue)) { a = a != elt.nextSibling.id && doc.getElementById(a); try { s = a.nextSibling; elt.appendChild(a); s.parentNode.insertBefore(a, s); if (/^[@#]/.test(elt.innerHTML)) { elt.innerHTML += ' <span>&#9654;</span>'; } else { elt.innerHTML = '<span>&#9654;</span>'; elt.className = 'sf-dump-ref'; } elt.className += ' sf-dump-toggle'; } catch (e) { if ('&' == elt.innerHTML.charAt(0)) { elt.innerHTML = '&#8230;'; elt.className = 'sf-dump-ref'; } } } } } if (doc.evaluate && Array.from && root.children.length > 1) { root.setAttribute('tabindex', 0); SearchState = function () { this.nodes = []; this.idx = 0; }; SearchState.prototype = { next: function () { if (this.isEmpty()) { return this.current(); } this.idx = this.idx < (this.nodes.length - 1) ? this.idx + 1 : 0; return this.current(); }, previous: function () { if (this.isEmpty()) { return this.current(); } this.idx = this.idx > 0 ? this.idx - 1 : (this.nodes.length - 1); return this.current(); }, isEmpty: function () { return 0 === this.count(); }, current: function () { if (this.isEmpty()) { return null; } return this.nodes[this.idx]; }, reset: function () { this.nodes = []; this.idx = 0; }, count: function () { return this.nodes.length; }, }; function showCurrent(state) { var currentNode = state.current(), currentRect, searchRect; if (currentNode) { reveal(currentNode); highlight(root, currentNode, state.nodes); if ('scrollIntoView' in currentNode) { currentNode.scrollIntoView(true); currentRect = currentNode.getBoundingClientRect(); searchRect = search.getBoundingClientRect(); if (currentRect.top < (searchRect.top + searchRect.height)) { window.scrollBy(0, -(searchRect.top + searchRect.height + 5)); } } } counter.textContent = (state.isEmpty() ? 0 : state.idx + 1) + ' of ' + state.count(); } var search = doc.createElement('div'); search.className = 'sf-dump-search-wrapper sf-dump-search-hidden'; search.innerHTML = ' <input type="text" class="sf-dump-search-input"> <span class="sf-dump-search-count">0 of 0<\/span> <button type="button" class="sf-dump-search-input-previous" tabindex="-1"> <svg viewBox="0 0 1792 1792" xmlns="http://www.w3.org/2000/svg"><path d="M1683 1331l-166 165q-19 19-45 19t-45-19L896 965l-531 531q-19 19-45 19t-45-19l-166-165q-19-19-19-45.5t19-45.5l742-741q19-19 45-19t45 19l742 741q19 19 19 45.5t-19 45.5z"\/><\/svg> <\/button> <button type="button" class="sf-dump-search-input-next" tabindex="-1"> <svg viewBox="0 0 1792 1792" xmlns="http://www.w3.org/2000/svg"><path d="M1683 808l-742 741q-19 19-45 19t-45-19L109 808q-19-19-19-45.5t19-45.5l166-165q19-19 45-19t45 19l531 531 531-531q19-19 45-19t45 19l166 165q19 19 19 45.5t-19 45.5z"\/><\/svg> <\/button> '; root.insertBefore(search, root.firstChild); var state = new SearchState(); var searchInput = search.querySelector('.sf-dump-search-input'); var counter = search.querySelector('.sf-dump-search-count'); var searchInputTimer = 0; var previousSearchQuery = ''; addEventListener(searchInput, 'keyup', function (e) { var searchQuery = e.target.value; /* Don't perform anything if the pressed key didn't change the query */ if (searchQuery === previousSearchQuery) { return; } previousSearchQuery = searchQuery; clearTimeout(searchInputTimer); searchInputTimer = setTimeout(function () { state.reset(); collapseAll(root); resetHighlightedNodes(root); if ('' === searchQuery) { counter.textContent = '0 of 0'; return; } var classMatches = [ "sf-dump-str", "sf-dump-key", "sf-dump-public", "sf-dump-protected", "sf-dump-private", ].map(xpathHasClass).join(' or '); var xpathResult = doc.evaluate('.//span[' + classMatches + '][contains(translate(child::text(), ' + xpathString(searchQuery.toUpperCase()) + ', ' + xpathString(searchQuery.toLowerCase()) + '), ' + xpathString(searchQuery.toLowerCase()) + ')]', root, null, XPathResult.ORDERED_NODE_ITERATOR_TYPE, null); while (node = xpathResult.iterateNext()) state.nodes.push(node); showCurrent(state); }, 400); }); Array.from(search.querySelectorAll('.sf-dump-search-input-next, .sf-dump-search-input-previous')).forEach(function (btn) { addEventListener(btn, 'click', function (e) { e.preventDefault(); -1 !== e.target.className.indexOf('next') ? state.next() : state.previous(); searchInput.focus(); collapseAll(root); showCurrent(state); }) }); addEventListener(root, 'keydown', function (e) { var isSearchActive = !/\bsf-dump-search-hidden\b/.test(search.className); if ((114 === e.keyCode && !isSearchActive) || (isCtrlKey(e) && 70 === e.keyCode)) { /* F3 or CMD/CTRL + F */ if (70 === e.keyCode && document.activeElement === searchInput) { /* * If CMD/CTRL + F is hit while having focus on search input, * the user probably meant to trigger browser search instead. * Let the browser execute its behavior: */ return; } e.preventDefault(); search.className = search.className.replace(/\bsf-dump-search-hidden\b/, ''); searchInput.focus(); } else if (isSearchActive) { if (27 === e.keyCode) { /* ESC key */ search.className += ' sf-dump-search-hidden'; e.preventDefault(); resetHighlightedNodes(root); searchInput.value = ''; } else if ( (isCtrlKey(e) && 71 === e.keyCode) /* CMD/CTRL + G */ || 13 === e.keyCode /* Enter */ || 114 === e.keyCode /* F3 */ ) { e.preventDefault(); e.shiftKey ? state.previous() : state.next(); collapseAll(root); showCurrent(state); } } }); } if (0 >= options.maxStringLength) { return; } try { elt = root.querySelectorAll('.sf-dump-str'); len = elt.length; i = 0; t = []; while (i < len) t.push(elt[i++]); len = t.length; for (i = 0; i < len; ++i) { elt = t[i]; s = elt.innerText || elt.textContent; x = s.length - options.maxStringLength; if (0 < x) { h = elt.innerHTML; elt[elt.innerText ? 'innerText' : 'textContent'] = s.substring(0, options.maxStringLength); elt.className += ' sf-dump-str-collapse'; elt.innerHTML = '<span class=sf-dump-str-collapse>'+h+'<a class="sf-dump-ref sf-dump-str-toggle" title="Collapse"> &#9664;</a></span>'+ '<span class=sf-dump-str-expand>'+elt.innerHTML+'<a class="sf-dump-ref sf-dump-str-toggle" title="'+x+' remaining characters"> &#9654;</a></span>'; } } } catch (e) { } }; })(document); </script><style> .sf-js-enabled pre.sf-dump .sf-dump-compact, .sf-js-enabled .sf-dump-str-collapse .sf-dump-str-collapse, .sf-js-enabled .sf-dump-str-expand .sf-dump-str-expand { display: none; } .sf-dump-hover:hover { background-color: #B729D9; color: #FFF !important; border-radius: 2px; } pre.sf-dump { display: block; white-space: pre; padding: 5px; overflow: initial !important; } pre.sf-dump:after { content: ""; visibility: hidden; display: block; height: 0; clear: both; } pre.sf-dump .sf-dump-ellipsization { display: inline-flex; } pre.sf-dump a { text-decoration: none; cursor: pointer; border: 0; outline: none; color: inherit; } pre.sf-dump img { max-width: 50em; max-height: 50em; margin: .5em 0 0 0; padding: 0; background: url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAAAAAA6mKC9AAAAHUlEQVQY02O8zAABilCaiQEN0EeA8QuUcX9g3QEAAjcC5piyhyEAAAAASUVORK5CYII=) #D3D3D3; } pre.sf-dump .sf-dump-ellipsis { text-overflow: ellipsis; white-space: nowrap; overflow: hidden; } pre.sf-dump .sf-dump-ellipsis-tail { flex-shrink: 0; } pre.sf-dump code { display:inline; padding:0; background:none; } .sf-dump-public.sf-dump-highlight, .sf-dump-protected.sf-dump-highlight, .sf-dump-private.sf-dump-highlight, .sf-dump-str.sf-dump-highlight, .sf-dump-key.sf-dump-highlight { background: rgba(111, 172, 204, 0.3); border: 1px solid #7DA0B1; border-radius: 3px; } .sf-dump-public.sf-dump-highlight-active, .sf-dump-protected.sf-dump-highlight-active, .sf-dump-private.sf-dump-highlight-active, .sf-dump-str.sf-dump-highlight-active, .sf-dump-key.sf-dump-highlight-active { background: rgba(253, 175, 0, 0.4); border: 1px solid #ffa500; border-radius: 3px; } pre.sf-dump .sf-dump-search-hidden { display: none !important; } pre.sf-dump .sf-dump-search-wrapper { font-size: 0; white-space: nowrap; margin-bottom: 5px; display: flex; position: -webkit-sticky; position: sticky; top: 5px; } pre.sf-dump .sf-dump-search-wrapper > * { vertical-align: top; box-sizing: border-box; height: 21px; font-weight: normal; border-radius: 0; background: #FFF; color: #757575; border: 1px solid #BBB; } pre.sf-dump .sf-dump-search-wrapper > input.sf-dump-search-input { padding: 3px; height: 21px; font-size: 12px; border-right: none; border-top-left-radius: 3px; border-bottom-left-radius: 3px; color: #000; min-width: 15px; width: 100%; } pre.sf-dump .sf-dump-search-wrapper > .sf-dump-search-input-next, pre.sf-dump .sf-dump-search-wrapper > .sf-dump-search-input-previous { background: #F2F2F2; outline: none; border-left: none; font-size: 0; line-height: 0; } pre.sf-dump .sf-dump-search-wrapper > .sf-dump-search-input-next { border-top-right-radius: 3px; border-bottom-right-radius: 3px; } pre.sf-dump .sf-dump-search-wrapper > .sf-dump-search-input-next > svg, pre.sf-dump .sf-dump-search-wrapper > .sf-dump-search-input-previous > svg { pointer-events: none; width: 12px; height: 12px; } pre.sf-dump .sf-dump-search-wrapper > .sf-dump-search-count { display: inline-block; padding: 0 5px; margin: 0; border-left: none; line-height: 21px; font-size: 12px; }pre.sf-dump, pre.sf-dump .sf-dump-default{background-color:#18171B; color:#FF8400; line-height:1.2em; font:12px Menlo, Monaco, Consolas, monospace; word-wrap: break-word; white-space: pre-wrap; position:relative; z-index:99999; word-break: break-all}pre.sf-dump .sf-dump-num{font-weight:bold; color:#1299DA}pre.sf-dump .sf-dump-const{font-weight:bold}pre.sf-dump .sf-dump-virtual{font-style:italic}pre.sf-dump .sf-dump-str{font-weight:bold; color:#56DB3A}pre.sf-dump .sf-dump-note{color:#1299DA}pre.sf-dump .sf-dump-ref{color:#A0A0A0}pre.sf-dump .sf-dump-public{color:#FFFFFF}pre.sf-dump .sf-dump-protected{color:#FFFFFF}pre.sf-dump .sf-dump-private{color:#FFFFFF}pre.sf-dump .sf-dump-meta{color:#B729D9}pre.sf-dump .sf-dump-key{color:#56DB3A}pre.sf-dump .sf-dump-index{color:#1299DA}pre.sf-dump .sf-dump-ellipsis{color:#FF8400}pre.sf-dump .sf-dump-ns{user-select:none;}pre.sf-dump .sf-dump-ellipsis-note{color:#1299DA}</style><pre class=sf-dump>"<span class=sf-dump-str title="3 characters">wow</span>"<span style="color: #A0A0A0;"> // test.php:10</span></pre>
HTML;

        // Anticipate

        Str::createUuidsUsing(fn () => Uuid::fromString($uuid));

        Http::fake(fn (Request $request) => Http::response(
            body: $dumpHtml,
            status: 500,
            headers: [],
        ));

        $parseResultDtoMock
            ->shouldReceive('toArray')
            ->andReturn([
                'source' => $stubSource,
                'dumps' => $stubDumps,
            ])
            ->once();

        $varDumpParserMock->shouldReceive('parse')->with($dumpHtml)->andReturn($parseResultDtoMock)->once();

        // Act

        $response = resolve(RequestRelayAction::class)->execute($requestData);

        // Assert

        $this->assertEquals(DieAndDumpResponse::DIE_AND_DUMP_STATUS_CODE, $response->statusCode);

        $this->assertEquals(
            [
                'id' => $uuid,
                'timestamp' => now()->format('Y-m-d H:i:s'),
                'source' => $stubSource,
                'dumps' => $stubDumps,
            ],
            $response->body->body,
        );
    }

    /*
     * Helpers.
     */

    private function getRandomAuthorizationCredentials(): AuthorizationCredentials
    {
        return Arr::random([
            AuthorizationCredentials::none(),
            new AuthorizationCredentials(AuthorizationTypeEnum::Basic, ['username' => 'user', 'password' => 'pass']),
            new AuthorizationCredentials(AuthorizationTypeEnum::Bearer, 'foobar'),
            new AuthorizationCredentials(AuthorizationTypeEnum::Impersonate, '14'),
            new AuthorizationCredentials(AuthorizationTypeEnum::CurrentUser, value: null),
        ]);
    }

    private function getEncryptedCookieValue(string $cookieName, string $rawValue): string
    {
        return app('encrypter')
            ->encrypt(
                value: CookieValuePrefix::create($cookieName, app('encrypter')->getKey()).$rawValue,
                serialize: false,
            );
    }

    private function mockAuthorizationHandler(): void
    {
        $dummyAuthorizationHandler = $this->mock(
            AuthorizationHandler::class,
            fn (MockInterface $mock) => $mock->shouldReceive('authorize')
                ->andReturnArg(0),
        );

        $this->mock(
            AuthorizationHandlerFactory::class,
            fn (MockInterface $mock) => $mock
                ->shouldReceive('create')
                ->andReturn($dummyAuthorizationHandler),
        );
    }

    /*
     * Asserts.
     */

    private function assertRelayResponseCookies(array $expectedCookies, array $actualCookies): void
    {
        $this->assertCount(
            count($expectedCookies),
            $actualCookies,
        );

        $this->assertContainsOnlyInstancesOf(
            ResponseCookieValueObject::class,
            $actualCookies,
        );

        foreach ($expectedCookies as $index => $expectedCookie) {
            $this->assertEquals(
                [
                    'key' => $expectedCookie['name'],
                    'value' => [
                        'raw' => $expectedCookie['raw'],
                        'decrypted' => $expectedCookie['decrypted'],
                    ],
                ],
                $actualCookies[$index]->toArray(),
            );
        }
    }
}

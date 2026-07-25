<?php

namespace Sunchayn\Nimbus\Tests\App\Modules\Relay\Services\VarDumpParser;

use Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Sunchayn\Nimbus\Modules\Relay\Services\VarDumpParser\DataTransferObjects\ObjectPropertyDto;
use Sunchayn\Nimbus\Modules\Relay\Services\VarDumpParser\DataTransferObjects\ParsedArrayResultDto;
use Sunchayn\Nimbus\Modules\Relay\Services\VarDumpParser\DataTransferObjects\ParsedClosureResultDto;
use Sunchayn\Nimbus\Modules\Relay\Services\VarDumpParser\DataTransferObjects\ParsedObjectResultDto;
use Sunchayn\Nimbus\Modules\Relay\Services\VarDumpParser\DataTransferObjects\ParsedValueDto;
use Sunchayn\Nimbus\Modules\Relay\Services\VarDumpParser\DataTransferObjects\ParseResultDto;
use Sunchayn\Nimbus\Modules\Relay\Services\VarDumpParser\Enums\DumpValueTypeEnum;
use Sunchayn\Nimbus\Modules\Relay\Services\VarDumpParser\VarDumpParser;

#[CoversClass(VarDumpParser::class)]
#[CoversClass(ParseResultDto::class)]
#[CoversClass(ParsedValueDto::class)]
#[CoversClass(ObjectPropertyDto::class)]
#[CoversClass(ParsedArrayResultDto::class)]
#[CoversClass(ParsedObjectResultDto::class)]
#[CoversClass(ParsedClosureResultDto::class)]
#[CoversClass(DumpValueTypeEnum::class)]
class VarDumpParserUnitTest extends TestCase
{
    private VarDumpParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new VarDumpParser;
    }

    #[DataProvider('primitiveTypesProvider')]
    public function test_it_parses_primitive_types(
        string $html,
        string $expectedType,
        mixed $expectedValue,
    ): void {
        // Act

        $result = $this->parser->parse($html);

        // Assert

        $dumps = $result->toArray()['dumps'];

        $this->assertJsonStructureMatches(
            [
                'type' => $expectedType,
                'value' => $expectedValue,
            ],
            $dumps[0],
        );
    }

    public static function primitiveTypesProvider(): Generator
    {
        // Strings
        yield 'simple string' => [
            'html' => '<pre class=sf-dump>"<span class=sf-dump-str title="3 characters">wow</span>"<span style="color: #A0A0A0;"> // test.php:10</span></pre>',
            'expectedType' => DumpValueTypeEnum::String->value,
            'expectedValue' => 'wow',
        ];

        yield 'empty string' => [
            'html' => '<pre class=sf-dump>"<span class=sf-dump-str title="0 characters"></span>"</pre>',
            'expectedType' => DumpValueTypeEnum::String->value,
            'expectedValue' => '',
        ];

        yield 'undefined string value' => [
            'html' => '<pre class=sf-dump>"<span class=sf-dump-str title="0 characters">"</pre>',
            'expectedType' => DumpValueTypeEnum::String->value,
            'expectedValue' => '',
        ];

        yield 'string with special characters' => [
            'html' => '<pre class=sf-dump>"<span class=sf-dump-str title="5 characters">&lt;div&gt;</span>"</pre>',
            'expectedType' => DumpValueTypeEnum::String->value,
            'expectedValue' => '<div>',
        ];

        yield 'string with ampersand' => [
            'html' => '<pre class=sf-dump>"<span class=sf-dump-str title="9 characters">Tom &amp; Jerry</span>"</pre>',
            'expectedType' => DumpValueTypeEnum::String->value,
            'expectedValue' => 'Tom & Jerry',
        ];

        yield 'string with quotes' => [
            'html' => '<pre class=sf-dump>"<span class=sf-dump-str title="8 characters">He said &quot;hi&quot;</span>"</pre>',
            'expectedType' => DumpValueTypeEnum::String->value,
            'expectedValue' => 'He said "hi"',
        ];

        yield 'string with angle brackets' => [
            'html' => '<pre class=sf-dump>"<span class=sf-dump-str title="11 characters">&lt;html&gt; tag</span>"</pre>',
            'expectedType' => DumpValueTypeEnum::String->value,
            'expectedValue' => '<html> tag',
        ];

        // Numbers
        yield 'positive integer' => [
            'html' => '<pre class=sf-dump><span class=sf-dump-num>42</span><span style="color: #A0A0A0;"> // test.php:10</span></pre>',
            'expectedType' => DumpValueTypeEnum::Number->value,
            'expectedValue' => 42,
        ];

        yield 'zero' => [
            'html' => '<pre class=sf-dump><span class=sf-dump-num>0</span></pre>',
            'expectedType' => DumpValueTypeEnum::Number->value,
            'expectedValue' => 0,
        ];

        yield 'undefined number value' => [
            'html' => '<pre class=sf-dump><span class=sf-dump-num></span></pre>',
            'expectedType' => DumpValueTypeEnum::Number->value,
            'expectedValue' => 0,
        ];

        yield 'negative integer' => [
            'html' => '<pre class=sf-dump><span class=sf-dump-num>-42</span></pre>',
            'expectedType' => DumpValueTypeEnum::Number->value,
            'expectedValue' => -42,
        ];

        yield 'float value' => [
            'html' => '<pre class=sf-dump><span class=sf-dump-num>3.14</span><span style="color: #A0A0A0;"> // test.php:10</span></pre>',
            'expectedType' => DumpValueTypeEnum::Number->value,
            'expectedValue' => 3.14,
        ];

        yield 'negative float' => [
            'html' => '<pre class=sf-dump><span class=sf-dump-num>-99.99</span></pre>',
            'expectedType' => DumpValueTypeEnum::Number->value,
            'expectedValue' => -99.99,
        ];

        // Constants
        yield 'boolean true' => [
            'html' => '<pre class=sf-dump><span class=sf-dump-const>true</span><span style="color: #A0A0A0;"> // test.php:10</span></pre>',
            'expectedType' => DumpValueTypeEnum::Constant->value,
            'expectedValue' => true,
        ];

        yield 'boolean false' => [
            'html' => '<pre class=sf-dump><span class=sf-dump-const>false</span><span style="color: #A0A0A0;"> // test.php:10</span></pre>',
            'expectedType' => DumpValueTypeEnum::Constant->value,
            'expectedValue' => false,
        ];

        yield 'null value' => [
            'html' => '<pre class=sf-dump><span class=sf-dump-const>null</span><span style="color: #A0A0A0;"> // test.php:10</span></pre>',
            'expectedType' => DumpValueTypeEnum::Constant->value,
            'expectedValue' => null,
        ];

        yield 'undefined const value' => [
            'html' => '<pre class=sf-dump><span class=sf-dump-const></span><span style="color: #A0A0A0;"> // test.php:10</span></pre>',
            'expectedType' => DumpValueTypeEnum::Constant->value,
            'expectedValue' => null,
        ];
    }

    #[DataProvider('arrayStructuresProvider')]
    public function test_it_parses_array_structures(
        string $html,
        array $expectedStructure,
    ): void {
        // Act

        $result = $this->parser->parse($html);

        // Assert

        $dump = $result->toArray()['dumps'][0];

        $this->assertEquals(DumpValueTypeEnum::Array->value, $dump['type']);

        $this->assertJsonStructureMatches(
            $expectedStructure,
            $dump['value'],
        );
    }

    public static function arrayStructuresProvider(): Generator
    {
        yield 'empty array' => [
            'html' => <<<'HTML'
<pre class=sf-dump id=sf-dump-265345762 data-indent-pad="  ">[]<span style="color: #A0A0A0;"> // app/Http/Controllers/Demo/DumpAndDieController.php:140</span>
</pre></pre><script>Sfdump("sf-dump-265345762")</script>
HTML,
            'expectedStructure' => [
                'length' => 0,
                'items' => [],
                'numericallyIndexed' => true,
            ],
        ];

        yield 'another empty array' => [
            'html' => <<<'HTML'
<pre class=sf-dump id=sf-dump-265345762 data-indent-pad="  "><span class=sf-dump-note>array:2</span> [ &#8230;2]<span style="color: #A0A0A0;"> // app/Http/Controllers/Demo/DumpAndDieController.php:140</span>
</pre>
HTML,
            'expectedStructure' => [
                'length' => 0,
                'items' => [],
                'numericallyIndexed' => true,
            ],
        ];

        yield 'indexed array with strings' => [
            'html' => <<<'HTML'
<pre class=sf-dump><span class=sf-dump-note>array:2</span> [<samp data-depth=1 class=sf-dump-expanded>
  <span class=sf-dump-index>0</span> => "<span class=sf-dump-str title="4 characters">Foo</span>"
  <span class=sf-dump-index>1</span> => "<span class=sf-dump-str title="5 characters">Bar</span>"
</samp>]</pre>
HTML,
            'expectedStructure' => [
                'length' => 2,
                'items' => [
                    0 => ['type' => DumpValueTypeEnum::String->value, 'value' => 'Foo'],
                    1 => ['type' => DumpValueTypeEnum::String->value, 'value' => 'Bar'],
                ],
                'numericallyIndexed' => true,
            ],
        ];

        yield 'non-indexed array with strings numerical indices' => [
            'html' => <<<'HTML'
<pre class=sf-dump><span class=sf-dump-note>array:2</span> [<samp data-depth=1 class=sf-dump-expanded>
  "<span class=sf-dump-key>0</span>" => "<span class=sf-dump-str title="3 characters">Foo</span>"
  "<span class=sf-dump-key>2</span>" => "<span class=sf-dump-str title="3 characters">Bar</span>"
  "<span class=sf-dump-key>4</span>" => "<span class=sf-dump-str title="3 characters">Baz</span>"
</samp>]</pre>
HTML,
            'expectedStructure' => [
                'length' => 3,
                'items' => [
                    0 => ['type' => DumpValueTypeEnum::String->value, 'value' => 'Foo'],
                    2 => ['type' => DumpValueTypeEnum::String->value, 'value' => 'Bar'],
                    4 => ['type' => DumpValueTypeEnum::String->value, 'value' => 'Baz'],
                ],
                'numericallyIndexed' => false,
            ],
        ];

        yield 'associative array with strings' => [
            'html' => <<<'HTML'
<pre class=sf-dump><span class=sf-dump-note>array:2</span> [<samp data-depth=1 class=sf-dump-expanded>
  "<span class=sf-dump-key>name</span>" => "<span class=sf-dump-str title="4 characters">John</span>"
  "<span class=sf-dump-key>role</span>" => "<span class=sf-dump-str title="5 characters">admin</span>"
</samp>]</pre>
HTML,
            'expectedStructure' => [
                'length' => 2,
                'items' => [
                    'name' => ['type' => DumpValueTypeEnum::String->value, 'value' => 'John'],
                    'role' => ['type' => DumpValueTypeEnum::String->value, 'value' => 'admin'],
                ],
                'numericallyIndexed' => false,
            ],
        ];

        yield 'array with mixed types' => [
            'html' => <<<'HTML'
<pre class=sf-dump><span class=sf-dump-note>array:3</span> [<samp data-depth=1 class=sf-dump-expanded>
  "<span class=sf-dump-key>name</span>" => "<span class=sf-dump-str title="4 characters">Jane</span>"
  "<span class=sf-dump-key>age</span>" => <span class=sf-dump-num>25</span>
  "<span class=sf-dump-key>active</span>" => <span class=sf-dump-const>true</span>
</samp>]</pre>
HTML,
            'expectedStructure' => [
                'length' => 3,
                'items' => [
                    'name' => ['type' => DumpValueTypeEnum::String->value, 'value' => 'Jane'],
                    'age' => ['type' => DumpValueTypeEnum::Number->value, 'value' => 25],
                    'active' => ['type' => DumpValueTypeEnum::Constant->value, 'value' => true],
                ],
                'numericallyIndexed' => false,
            ],
        ];

        yield 'nested array' => [
            'html' => <<<'HTML'
<pre class=sf-dump><span class=sf-dump-note>array:2</span> [<samp data-depth=1 class=sf-dump-expanded>
  "<span class=sf-dump-key>user</span>" => "<span class=sf-dump-str title="4 characters">John</span>"
  "<span class=sf-dump-key>meta</span>" => <span class=sf-dump-note>array:2</span> [<samp data-depth=2 class=sf-dump-compact>
    "<span class=sf-dump-key>role</span>" => "<span class=sf-dump-str title="5 characters">admin</span>"
    "<span class=sf-dump-key>level</span>" => <span class=sf-dump-num>5</span>
  </samp>]
</samp>]</pre>
HTML,
            'expectedStructure' => [
                'length' => 2,
                'items' => [
                    'user' => ['type' => DumpValueTypeEnum::String->value, 'value' => 'John'],
                    'meta' => [
                        'type' => DumpValueTypeEnum::Array->value,
                        'value' => [
                            'length' => 2,
                            'items' => [
                                'role' => ['type' => DumpValueTypeEnum::String->value, 'value' => 'admin'],
                                'level' => ['type' => DumpValueTypeEnum::Number->value, 'value' => 5],
                            ],
                            'numericallyIndexed' => false,
                        ],
                    ],
                ],
                'numericallyIndexed' => false,
            ],
        ];
    }

    #[DataProvider('objectStructuresProvider')]
    public function test_it_parses_object_structures(
        string $html,
        ?string $expectedClass,
        array $expectedProperties,
    ): void {
        // Act

        $result = $this->parser->parse($html);

        // Assert

        $dumps = $result->toArray()['dumps'];

        $this->assertJsonStructureMatches(
            [
                'type' => DumpValueTypeEnum::Object->value,
                'value' => [
                    'class' => $expectedClass,
                    'properties' => $expectedProperties,
                    'propertiesCount' => count($expectedProperties),
                ],
            ],
            $dumps[0],
        );
    }

    public static function objectStructuresProvider(): Generator
    {
        yield 'object with public properties (and improper indentation)' => [
            'html' => <<<'HTML'
<pre class=sf-dump><span class=sf-dump-note>App\Models\User</span> {<a class=sf-dump-ref>#123</a><samp data-depth=1 class=sf-dump-expanded>
+<span class=sf-dump-public title="Public property">id</span>: <span class=sf-dump-num>1</span>
+<span class=sf-dump-public title="Public property">name</span>: "<span class=sf-dump-str title="4 characters">John</span>"
</samp>}</pre>
HTML,
            'expectedClass' => 'App\Models\User',
            'expectedProperties' => [
                'id' => [
                    'visibility' => 'public',
                    'value' => ['type' => DumpValueTypeEnum::Number->value, 'value' => 1],
                ],
                'name' => [
                    'visibility' => 'public',
                    'value' => ['type' => DumpValueTypeEnum::String->value, 'value' => 'John'],
                ],
            ],
        ];

        yield 'object with mixed visibility properties' => [
            'html' => <<<'HTML'
<pre class=sf-dump><span class=sf-dump-note>App\Models\User</span> {<a class=sf-dump-ref>#456</a><samp data-depth=1 class=sf-dump-expanded>
  +<span class=sf-dump-public title="Public property">id</span>: <span class=sf-dump-num>1</span>
  #<span class=sf-dump-protected title="Protected property">password</span>: "<span class=sf-dump-str title="6 characters">secret</span>"
  -<span class=sf-dump-private title="Private property">token</span>: "<span class=sf-dump-str title="5 characters">12345</span>"
</samp>}</pre>
HTML,
            'expectedClass' => 'App\Models\User',
            'expectedProperties' => [
                'id' => [
                    'visibility' => 'public',
                    'value' => ['type' => DumpValueTypeEnum::Number->value, 'value' => 1],
                ],
                'password' => [
                    'visibility' => 'protected',
                    'value' => ['type' => DumpValueTypeEnum::String->value, 'value' => 'secret'],
                ],
                'token' => [
                    'visibility' => 'private',
                    'value' => ['type' => DumpValueTypeEnum::String->value, 'value' => '12345'],
                ],
            ],
        ];

        yield 'runtime object with nested structures' => [
            'html' => <<<'HTML'
<script> Sfdump = window.Sfdump || (function (doc) { doc.documentElement.classList.add('sf-js-enabled'); var rxEsc = /([.*+?^${}()|\[\]\/\\])/g, idRx = /\bsf-dump-\d+-ref[012]\w+\b/, keyHint = 0 <= navigator.platform.toUpperCase().indexOf('MAC') ? 'Cmd' : 'Ctrl', addEventListener = function (e, n, cb) { e.addEventListener(n, cb, false); }; if (!doc.addEventListener) { addEventListener = function (element, eventName, callback) { element.attachEvent('on' + eventName, function (e) { e.preventDefault = function () {e.returnValue = false;}; e.target = e.srcElement; callback(e); }); }; } function toggle(a, recursive) { var s = a.nextSibling || {}, oldClass = s.className, arrow, newClass; if (/\bsf-dump-compact\b/.test(oldClass)) { arrow = '&#9660;'; newClass = 'sf-dump-expanded'; } else if (/\bsf-dump-expanded\b/.test(oldClass)) { arrow = '&#9654;'; newClass = 'sf-dump-compact'; } else { return false; } if (doc.createEvent && s.dispatchEvent) { var event = doc.createEvent('Event'); event.initEvent('sf-dump-expanded' === newClass ? 'sfbeforedumpexpand' : 'sfbeforedumpcollapse', true, false); s.dispatchEvent(event); } a.lastChild.innerHTML = arrow; s.className = s.className.replace(/\bsf-dump-(compact|expanded)\b/, newClass); if (recursive) { try { a = s.querySelectorAll('.'+oldClass); for (s = 0; s < a.length; ++s) { if (-1 == a[s].className.indexOf(newClass)) { a[s].className = newClass; a[s].previousSibling.lastChild.innerHTML = arrow; } } } catch (e) { } } return true; }; function collapse(a, recursive) { var s = a.nextSibling || {}, oldClass = s.className; if (/\bsf-dump-expanded\b/.test(oldClass)) { toggle(a, recursive); return true; } return false; }; function expand(a, recursive) { var s = a.nextSibling || {}, oldClass = s.className; if (/\bsf-dump-compact\b/.test(oldClass)) { toggle(a, recursive); return true; } return false; }; function collapseAll(root) { var a = root.querySelector('a.sf-dump-toggle'); if (a) { collapse(a, true); expand(a); return true; } return false; } function reveal(node) { var previous, parents = []; while ((node = node.parentNode || {}) && (previous = node.previousSibling) && 'A' === previous.tagName) { parents.push(previous); } if (0 !== parents.length) { parents.forEach(function (parent) { expand(parent); }); return true; } return false; } function highlight(root, activeNode, nodes) { resetHighlightedNodes(root); Array.from(nodes||[]).forEach(function (node) { if (!/\bsf-dump-highlight\b/.test(node.className)) { node.className = node.className + ' sf-dump-highlight'; } }); if (!/\bsf-dump-highlight-active\b/.test(activeNode.className)) { activeNode.className = activeNode.className + ' sf-dump-highlight-active'; } } function resetHighlightedNodes(root) { Array.from(root.querySelectorAll('.sf-dump-str, .sf-dump-key, .sf-dump-public, .sf-dump-protected, .sf-dump-private')).forEach(function (strNode) { strNode.className = strNode.className.replace(/\bsf-dump-highlight\b/, ''); strNode.className = strNode.className.replace(/\bsf-dump-highlight-active\b/, ''); }); } return function (root, x) { root = doc.getElementById(root); var indentRx = new RegExp('^('+(root.getAttribute('data-indent-pad') || ' ').replace(rxEsc, '\\$1')+')+', 'm'), options = {"maxDepth":1,"maxStringLength":160,"fileLinkFormat":false}, elt = root.getElementsByTagName('A'), len = elt.length, i = 0, s, h, t = []; while (i < len) t.push(elt[i++]); for (i in x) { options[i] = x[i]; } function a(e, f) { addEventListener(root, e, function (e, n) { if ('A' == e.target.tagName) { f(e.target, e); } else if ('A' == e.target.parentNode.tagName) { f(e.target.parentNode, e); } else { n = /\bsf-dump-ellipsis\b/.test(e.target.className) ? e.target.parentNode : e.target; if ((n = n.nextElementSibling) && 'A' == n.tagName) { if (!/\bsf-dump-toggle\b/.test(n.className)) { n = n.nextElementSibling || n; } f(n, e, true); } } }); }; function isCtrlKey(e) { return e.ctrlKey || e.metaKey; } function xpathString(str) { var parts = str.match(/[^'"]+|['"]/g).map(function (part) { if ("'" == part) { return '"\'"'; } if ('"' == part) { return "'\"'"; } return "'" + part + "'"; }); return "concat(" + parts.join(",") + ", '')"; } function xpathHasClass(className) { return "contains(concat(' ', normalize-space(@class), ' '), ' " + className +" ')"; } a('mouseover', function (a, e, c) { if (c) { e.target.style.cursor = "pointer"; } }); a('click', function (a, e, c) { if (/\bsf-dump-toggle\b/.test(a.className)) { e.preventDefault(); if (!toggle(a, isCtrlKey(e))) { var r = doc.getElementById(a.getAttribute('href').slice(1)), s = r.previousSibling, f = r.parentNode, t = a.parentNode; t.replaceChild(r, a); f.replaceChild(a, s); t.insertBefore(s, r); f = f.firstChild.nodeValue.match(indentRx); t = t.firstChild.nodeValue.match(indentRx); if (f && t && f[0] !== t[0]) { r.innerHTML = r.innerHTML.replace(new RegExp('^'+f[0].replace(rxEsc, '\\$1'), 'mg'), t[0]); } if (/\bsf-dump-compact\b/.test(r.className)) { toggle(s, isCtrlKey(e)); } } if (c) { } else if (doc.getSelection) { try { doc.getSelection().removeAllRanges(); } catch (e) { doc.getSelection().empty(); } } else { doc.selection.empty(); } } else if (/\bsf-dump-str-toggle\b/.test(a.className)) { e.preventDefault(); e = a.parentNode.parentNode; e.className = e.className.replace(/\bsf-dump-str-(expand|collapse)\b/, a.parentNode.className); } }); elt = root.getElementsByTagName('SAMP'); len = elt.length; i = 0; while (i < len) t.push(elt[i++]); len = t.length; for (i = 0; i < len; ++i) { elt = t[i]; if ('SAMP' == elt.tagName) { a = elt.previousSibling || {}; if ('A' != a.tagName) { a = doc.createElement('A'); a.className = 'sf-dump-ref'; elt.parentNode.insertBefore(a, elt); } else { a.innerHTML += ' '; } a.title = (a.title ? a.title+'\n[' : '[')+keyHint+'+click] Expand all children'; a.innerHTML += elt.className == 'sf-dump-compact' ? '<span>&#9654;</span>' : '<span>&#9660;</span>'; a.className += ' sf-dump-toggle'; x = 1; if ('sf-dump' != elt.parentNode.className) { x += elt.parentNode.getAttribute('data-depth')/1; } } else if (/\bsf-dump-ref\b/.test(elt.className) && (a = elt.getAttribute('href'))) { a = a.slice(1); elt.className += ' sf-dump-hover'; elt.className += ' '+a; if (/[\[{]$/.test(elt.previousSibling.nodeValue)) { a = a != elt.nextSibling.id && doc.getElementById(a); try { s = a.nextSibling; elt.appendChild(a); s.parentNode.insertBefore(a, s); if (/^[@#]/.test(elt.innerHTML)) { elt.innerHTML += ' <span>&#9654;</span>'; } else { elt.innerHTML = '<span>&#9654;</span>'; elt.className = 'sf-dump-ref'; } elt.className += ' sf-dump-toggle'; } catch (e) { if ('&' == elt.innerHTML.charAt(0)) { elt.innerHTML = '&#8230;'; elt.className = 'sf-dump-ref'; } } } } } if (doc.evaluate && Array.from && root.children.length > 1) { root.setAttribute('tabindex', 0); SearchState = function () { this.nodes = []; this.idx = 0; }; SearchState.prototype = { next: function () { if (this.isEmpty()) { return this.current(); } this.idx = this.idx < (this.nodes.length - 1) ? this.idx + 1 : 0; return this.current(); }, previous: function () { if (this.isEmpty()) { return this.current(); } this.idx = this.idx > 0 ? this.idx - 1 : (this.nodes.length - 1); return this.current(); }, isEmpty: function () { return 0 === this.count(); }, current: function () { if (this.isEmpty()) { return null; } return this.nodes[this.idx]; }, reset: function () { this.nodes = []; this.idx = 0; }, count: function () { return this.nodes.length; }, }; function showCurrent(state) { var currentNode = state.current(), currentRect, searchRect; if (currentNode) { reveal(currentNode); highlight(root, currentNode, state.nodes); if ('scrollIntoView' in currentNode) { currentNode.scrollIntoView(true); currentRect = currentNode.getBoundingClientRect(); searchRect = search.getBoundingClientRect(); if (currentRect.top < (searchRect.top + searchRect.height)) { window.scrollBy(0, -(searchRect.top + searchRect.height + 5)); } } } counter.textContent = (state.isEmpty() ? 0 : state.idx + 1) + ' of ' + state.count(); } var search = doc.createElement('div'); search.className = 'sf-dump-search-wrapper sf-dump-search-hidden'; search.innerHTML = ' <input type="text" class="sf-dump-search-input"> <span class="sf-dump-search-count">0 of 0<\/span> <button type="button" class="sf-dump-search-input-previous" tabindex="-1"> <svg viewBox="0 0 1792 1792" xmlns="http://www.w3.org/2000/svg"><path d="M1683 1331l-166 165q-19 19-45 19t-45-19L896 965l-531 531q-19 19-45 19t-45-19l-166-165q-19-19-19-45.5t19-45.5l742-741q19-19 45-19t45 19l742 741q19 19 19 45.5t-19 45.5z"\/><\/svg> <\/button> <button type="button" class="sf-dump-search-input-next" tabindex="-1"> <svg viewBox="0 0 1792 1792" xmlns="http://www.w3.org/2000/svg"><path d="M1683 808l-742 741q-19 19-45 19t-45-19L109 808q-19-19-19-45.5t19-45.5l166-165q19-19 45-19t45 19l531 531 531-531q19-19 45-19t45 19l166 165q19 19 19 45.5t-19 45.5z"\/><\/svg> <\/button> '; root.insertBefore(search, root.firstChild); var state = new SearchState(); var searchInput = search.querySelector('.sf-dump-search-input'); var counter = search.querySelector('.sf-dump-search-count'); var searchInputTimer = 0; var previousSearchQuery = ''; addEventListener(searchInput, 'keyup', function (e) { var searchQuery = e.target.value; /* Don't perform anything if the pressed key didn't change the query */ if (searchQuery === previousSearchQuery) { return; } previousSearchQuery = searchQuery; clearTimeout(searchInputTimer); searchInputTimer = setTimeout(function () { state.reset(); collapseAll(root); resetHighlightedNodes(root); if ('' === searchQuery) { counter.textContent = '0 of 0'; return; } var classMatches = [ "sf-dump-str", "sf-dump-key", "sf-dump-public", "sf-dump-protected", "sf-dump-private", ].map(xpathHasClass).join(' or '); var xpathResult = doc.evaluate('.//span[' + classMatches + '][contains(translate(child::text(), ' + xpathString(searchQuery.toUpperCase()) + ', ' + xpathString(searchQuery.toLowerCase()) + '), ' + xpathString(searchQuery.toLowerCase()) + ')]', root, null, XPathResult.ORDERED_NODE_ITERATOR_TYPE, null); while (node = xpathResult.iterateNext()) state.nodes.push(node); showCurrent(state); }, 400); }); Array.from(search.querySelectorAll('.sf-dump-search-input-next, .sf-dump-search-input-previous')).forEach(function (btn) { addEventListener(btn, 'click', function (e) { e.preventDefault(); -1 !== e.target.className.indexOf('next') ? state.next() : state.previous(); searchInput.focus(); collapseAll(root); showCurrent(state); }) }); addEventListener(root, 'keydown', function (e) { var isSearchActive = !/\bsf-dump-search-hidden\b/.test(search.className); if ((114 === e.keyCode && !isSearchActive) || (isCtrlKey(e) && 70 === e.keyCode)) { /* F3 or CMD/CTRL + F */ if (70 === e.keyCode && document.activeElement === searchInput) {                 /* * If CMD/CTRL + F is hit while having focus on search input, * the user probably meant to trigger browser search instead. * Let the browser execute its behavior: */ return; } e.preventDefault(); search.className = search.className.replace(/\bsf-dump-search-hidden\b/, ''); searchInput.focus(); } else if (isSearchActive) { if (27 === e.keyCode) { /* ESC key */ search.className += ' sf-dump-search-hidden'; e.preventDefault(); resetHighlightedNodes(root); searchInput.value = ''; } else if ( (isCtrlKey(e) && 71 === e.keyCode) /* CMD/CTRL + G */ || 13 === e.keyCode /* Enter */ || 114 === e.keyCode /* F3 */ ) { e.preventDefault(); e.shiftKey ? state.previous() : state.next(); collapseAll(root); showCurrent(state); } } }); } if (0 >= options.maxStringLength) { return; } try { elt = root.querySelectorAll('.sf-dump-str'); len = elt.length; i = 0; t = []; while (i < len) t.push(elt[i++]); len = t.length; for (i = 0; i < len; ++i) { elt = t[i]; s = elt.innerText || elt.textContent; x = s.length - options.maxStringLength; if (0 < x) { h = elt.innerHTML; elt[elt.innerText ? 'innerText' : 'textContent'] = s.substring(0, options.maxStringLength); elt.className += ' sf-dump-str-collapse'; elt.innerHTML = '<span class=sf-dump-str-collapse>'+h+'<a class="sf-dump-ref sf-dump-str-toggle" title="Collapse"> &#9664;</a></span>'+ '<span class=sf-dump-str-expand>'+elt.innerHTML+'<a class="sf-dump-ref sf-dump-str-toggle" title="'+x+' remaining characters"> &#9654;</a></span>'; } } } catch (e) { } }; })(document); </script><style> .sf-js-enabled pre.sf-dump .sf-dump-compact, .sf-js-enabled .sf-dump-str-collapse .sf-dump-str-collapse, .sf-js-enabled .sf-dump-str-expand .sf-dump-str-expand { display: none; } .sf-dump-hover:hover { background-color: #B729D9; color: #FFF !important; border-radius: 2px; } pre.sf-dump { display: block; white-space: pre; padding: 5px; overflow: initial !important; } pre.sf-dump:after { content: ""; visibility: hidden; display: block; height: 0; clear: both; } pre.sf-dump .sf-dump-ellipsization { display: inline-flex; } pre.sf-dump a { text-decoration: none; cursor: pointer; border: 0; outline: none; color: inherit; } pre.sf-dump img { max-width: 50em; max-height: 50em; margin: .5em 0 0 0; padding: 0; background: url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAAAAAA6mKC9AAAAHUlEQVQY02O8zAABilCaiQEN0EeA8QuUcX9g3QEAAjcC5piyhyEAAAAASUVORK5CYII=) #D3D3D3; } pre.sf-dump .sf-dump-ellipsis { text-overflow: ellipsis; white-space: nowrap; overflow: hidden; } pre.sf-dump .sf-dump-ellipsis-tail { flex-shrink: 0; } pre.sf-dump code { display:inline; padding:0; background:none; } .sf-dump-public.sf-dump-highlight, .sf-dump-protected.sf-dump-highlight, .sf-dump-private.sf-dump-highlight, .sf-dump-str.sf-dump-highlight, .sf-dump-key.sf-dump-highlight { background: rgba(111, 172, 204, 0.3); border: 1px solid #7DA0B1; border-radius: 3px; } .sf-dump-public.sf-dump-highlight-active, .sf-dump-protected.sf-dump-highlight-active, .sf-dump-private.sf-dump-highlight-active, .sf-dump-str.sf-dump-highlight-active, .sf-dump-key.sf-dump-highlight-active { background: rgba(253, 175, 0, 0.4); border: 1px solid #ffa500; border-radius: 3px; } pre.sf-dump .sf-dump-search-hidden { display: none !important; } pre.sf-dump .sf-dump-search-wrapper { font-size: 0; white-space: nowrap; margin-bottom: 5px; display: flex; position: -webkit-sticky; position: sticky; top: 5px; } pre.sf-dump .sf-dump-search-wrapper > * { vertical-align: top; box-sizing: border-box; height: 21px; font-weight: normal; border-radius: 0; background: #FFF; color: #757575; border: 1px solid #BBB; } pre.sf-dump .sf-dump-search-wrapper > input.sf-dump-search-input { padding: 3px; height: 21px; font-size: 12px; border-right: none; border-top-left-radius: 3px; border-bottom-left-radius: 3px; color: #000; min-width: 15px; width: 100%; } pre.sf-dump .sf-dump-search-wrapper > .sf-dump-search-input-next, pre.sf-dump .sf-dump-search-wrapper > .sf-dump-search-input-previous { background: #F2F2F2; outline: none; border-left: none; font-size: 0; line-height: 0; } pre.sf-dump .sf-dump-search-wrapper > .sf-dump-search-input-next { border-top-right-radius: 3px; border-bottom-right-radius: 3px; } pre.sf-dump .sf-dump-search-wrapper > .sf-dump-search-input-next > svg, pre.sf-dump .sf-dump-search-wrapper > .sf-dump-search-input-previous > svg { pointer-events: none; width: 12px; height: 12px; } pre.sf-dump .sf-dump-search-wrapper > .sf-dump-search-count { display: inline-block; padding: 0 5px; margin: 0; border-left: none; line-height: 21px; font-size: 12px; }pre.sf-dump, pre.sf-dump .sf-dump-default{background-color:#18171B; color:#FF8400; line-height:1.2em; font:12px Menlo, Monaco, Consolas, monospace; word-wrap: break-word; white-space: pre-wrap; position:relative; z-index:99999; word-break: break-all}pre.sf-dump .sf-dump-num{font-weight:bold; color:#1299DA}pre.sf-dump .sf-dump-const{font-weight:bold}pre.sf-dump .sf-dump-virtual{font-style:italic}pre.sf-dump .sf-dump-str{font-weight:bold; color:#56DB3A}pre.sf-dump .sf-dump-note{color:#1299DA}pre.sf-dump .sf-dump-ref{color:#A0A0A0}pre.sf-dump .sf-dump-public{color:#FFFFFF}pre.sf-dump .sf-dump-protected{color:#FFFFFF}pre.sf-dump .sf-dump-private{color:#FFFFFF}pre.sf-dump .sf-dump-meta{color:#B729D9}pre.sf-dump .sf-dump-key{color:#56DB3A}pre.sf-dump .sf-dump-index{color:#1299DA}pre.sf-dump .sf-dump-ellipsis{color:#FF8400}pre.sf-dump .sf-dump-ns{user-select:none;}pre.sf-dump .sf-dump-ellipsis-note{color:#1299DA}</style><pre class=sf-dump id=sf-dump-1372504472 data-indent-pad="  ">{<a class=sf-dump-ref>#404</a><samp data-depth=1 class=sf-dump-expanded><span style="color: #A0A0A0;"> // app/Http/Controllers/Demo/DumpAndDieController.php:140</span>
  +"<span class=sf-dump-public title="Runtime added dynamic property">id</span>": <span class=sf-dump-num>42</span>
  +"<span class=sf-dump-public title="Runtime added dynamic property">name</span>": "<span class=sf-dump-str title="22 characters">Laravel Framework Book</span>"
  +"<span class=sf-dump-public title="Runtime added dynamic property">price</span>": <span class=sf-dump-num>49.99</span>
  +"<span class=sf-dump-public title="Runtime added dynamic property">inStock</span>": <span class=sf-dump-const>true</span>
  +"<span class=sf-dump-public title="Runtime added dynamic property">tags</span>": <span class=sf-dump-note>array:4</span> [<samp data-depth=2 class=sf-dump-compact>
    <span class=sf-dump-index>0</span> => "<span class=sf-dump-str title="3 characters">php</span>"
    <span class=sf-dump-index>1</span> => "<span class=sf-dump-str title="7 characters">laravel</span>"
    <span class=sf-dump-index>2</span> => "<span class=sf-dump-str title="9 characters">framework</span>"
    <span class=sf-dump-index>3</span> => "<span class=sf-dump-str title="15 characters">web-development</span>"
  </samp>]
  +"<span class=sf-dump-public title="Runtime added dynamic property">reviews</span>": <span class=sf-dump-note>array:1</span> [<samp data-depth=2 class=sf-dump-compact>
    <span class=sf-dump-index>0</span> => {<a class=sf-dump-ref>#405</a><samp data-depth=3 class=sf-dump-compact>
      +"<span class=sf-dump-public title="Runtime added dynamic property">id</span>": <span class=sf-dump-num>1</span>
      +"<span class=sf-dump-public title="Runtime added dynamic property">rating</span>": <span class=sf-dump-num>5</span>
      +"<span class=sf-dump-public title="Runtime added dynamic property">comment</span>": "<span class=sf-dump-str title="15 characters">Excellent book!</span>"
      +"<span class=sf-dump-public title="Runtime added dynamic property">author</span>": {<a class=sf-dump-ref>#406</a><samp data-depth=4 class=sf-dump-compact>
        +"<span class=sf-dump-public title="Runtime added dynamic property">id</span>": <span class=sf-dump-num>10</span>
        +"<span class=sf-dump-public title="Runtime added dynamic property">name</span>": "<span class=sf-dump-str title="13 characters">Alice Johnson</span>"
      </samp>}
    </samp>}
  </samp>]
  #<span class=sf-dump-protected title="Protected property">environmentResolver</span>: <span class="sf-dump-note sf-dump-ellipsization" title="Illuminate\Foundation\Application::environment(...$environments)
"><span class="sf-dump-ellipsis sf-dump-ellipsis-note">Illuminate\Foundation</span><span class="sf-dump-ellipsis sf-dump-ellipsis-note">\</span><span class="sf-dump-ellipsis-tail">Application::environment(...$environments)</span></span> {<a class=sf-dump-ref>#128</a><samp data-depth=2 class=sf-dump-compact>
    <span class=sf-dump-meta>this</span>: <span class="sf-dump-note sf-dump-ellipsization" title="Illuminate\Foundation\Application
"><span class="sf-dump-ellipsis sf-dump-ellipsis-note">Illuminate\Foundation</span><span class="sf-dump-ellipsis sf-dump-ellipsis-note">\</span><span class="sf-dump-ellipsis-tail">Application</span></span> {<a class=sf-dump-ref href=#sf-dump-1386608666-ref24 title="102 occurrences">#4</a>}
  #<span class=sf-dump-protected title="Protected property">bootedCallbacks</span>: <span class=sf-dump-note>array:2</span> [<samp data-depth=2 class=sf-dump-compact>
    <span class=sf-dump-index>0</span> => <span class=sf-dump-note>Closure($app)</span> {<a class=sf-dump-ref>#222</a><samp data-depth=3 class=sf-dump-compact>
      <span class=sf-dump-meta>class</span>: "<span class="sf-dump-str sf-dump-ellipsization" title="Illuminate\Filesystem\FilesystemServiceProvider
47 characters"><span class="sf-dump-ellipsis sf-dump-ellipsis-class">Illuminate\Filesystem</span><span class="sf-dump-ellipsis sf-dump-ellipsis-class">\</span><span class="sf-dump-ellipsis-tail">FilesystemServiceProvider</span></span>"
      <span class=sf-dump-meta>this</span>: <span class="sf-dump-note sf-dump-ellipsization" title="Illuminate\Filesystem\FilesystemServiceProvider
"><span class="sf-dump-ellipsis sf-dump-ellipsis-note">Illuminate\Filesystem</span><span class="sf-dump-ellipsis sf-dump-ellipsis-note">\</span><span class="sf-dump-ellipsis-tail">FilesystemServiceProvider</span></span> {<a class=sf-dump-ref href=#sf-dump-1336641337-ref294 title="6 occurrences">#94</a>}
      <span class=sf-dump-meta>use</span>: {<samp data-depth=4 class=sf-dump-compact>
        <span class=sf-dump-meta>$disk</span>: "<span class=sf-dump-str title="5 characters">local</span>"
        <span class=sf-dump-meta>$config</span>: <span class=sf-dump-note>array:5</span> [<samp data-depth=5 class=sf-dump-compact>
          "<span class=sf-dump-key>driver</span>" => "<span class=sf-dump-str title="5 characters">local</span>"
          "<span class=sf-dump-key>root</span>" => "<span class=sf-dump-str title="43 characters">/Volumes/Dev/nimbus-dev/storage/app/private</span>"
          "<span class=sf-dump-key>serve</span>" => <span class=sf-dump-const>true</span>
          "<span class=sf-dump-key>throw</span>" => <span class=sf-dump-const>false</span>
          "<span class=sf-dump-key>report</span>" => <span class=sf-dump-const>false</span>
        </samp>]
      </samp>}
    </samp>}
    <span class=sf-dump-index>1</span> => <span class=sf-dump-note>Closure()</span> {<a class=sf-dump-ref>#289</a><samp data-depth=3 class=sf-dump-compact>
      <span class=sf-dump-meta>class</span>: "<span class="sf-dump-str sf-dump-ellipsization" title="Illuminate\Foundation\Support\Providers\RouteServiceProvider
60 characters"><span class="sf-dump-ellipsis sf-dump-ellipsis-class">Illuminate\Foundation\Support\Providers</span><span class="sf-dump-ellipsis sf-dump-ellipsis-class">\</span><span class="sf-dump-ellipsis-tail">RouteServiceProvider</span></span>"
      <span class=sf-dump-meta>this</span>: <span class="sf-dump-note sf-dump-ellipsization" title="Illuminate\Foundation\Support\Providers\RouteServiceProvider
"><span class="sf-dump-ellipsis sf-dump-ellipsis-note">Illuminate\Foundation\Support\Providers</span><span class="sf-dump-ellipsis sf-dump-ellipsis-note">\</span><span class="sf-dump-ellipsis-tail">RouteServiceProvider</span></span> {<a class=sf-dump-ref href=#sf-dump-1336641337-ref2131 title="3 occurrences">#131</a><samp data-depth=4 id=sf-dump-1336641337-ref2131 class=sf-dump-compact>
        #<span class=sf-dump-protected title="Protected property">app</span>: <span class="sf-dump-note sf-dump-ellipsization" title="Illuminate\Foundation\Application
"><span class="sf-dump-ellipsis sf-dump-ellipsis-note">Illuminate\Foundation</span><span class="sf-dump-ellipsis sf-dump-ellipsis-note">\</span><span class="sf-dump-ellipsis-tail">Application</span></span> {<a class=sf-dump-ref href=#sf-dump-1336641337-ref24 title="101 occurrences">#4</a>}
        #<span class=sf-dump-protected title="Protected property">bootingCallbacks</span>: []
        #<span class=sf-dump-protected title="Protected property">bootedCallbacks</span>: <span class=sf-dump-note>array:1</span> [<samp data-depth=5 class=sf-dump-compact>
          <span class=sf-dump-index>0</span> => <span class=sf-dump-note>Closure()</span> {<a class=sf-dump-ref>#116</a><samp data-depth=6 class=sf-dump-compact>
            <span class=sf-dump-meta>class</span>: "<span class="sf-dump-str sf-dump-ellipsization" title="Illuminate\Foundation\Support\Providers\RouteServiceProvider
60 characters"><span class="sf-dump-ellipsis sf-dump-ellipsis-class">Illuminate\Foundation\Support\Providers</span><span class="sf-dump-ellipsis sf-dump-ellipsis-class">\</span><span class="sf-dump-ellipsis-tail">RouteServiceProvider</span></span>"
            <span class=sf-dump-meta>this</span>: <span class="sf-dump-note sf-dump-ellipsization" title="Illuminate\Foundation\Support\Providers\RouteServiceProvider
"><span class="sf-dump-ellipsis sf-dump-ellipsis-note">Illuminate\Foundation\Support\Providers</span><span class="sf-dump-ellipsis sf-dump-ellipsis-note">\</span><span class="sf-dump-ellipsis-tail">RouteServiceProvider</span></span> {<a class=sf-dump-ref href=#sf-dump-1336641337-ref2131 title="3 occurrences">#131</a>}
          </samp>}
        </samp>]
        #<span class=sf-dump-protected title="Protected property">namespace</span>: <span class=sf-dump-const>null</span>
        #<span class=sf-dump-protected title="Protected property">loadRoutesUsing</span>: <span class=sf-dump-const>null</span>
      </samp>}
    </samp>}
  </samp>]
</samp>}
</pre><script>Sfdump("sf-dump-1372504472")</script>
HTML,
            'expectedClass' => '<runtime object>',
            'expectedProperties' => [
                'id' => [
                    'visibility' => 'public',
                    'value' => ['type' => DumpValueTypeEnum::Number->value, 'value' => 42],
                ],
                'name' => [
                    'visibility' => 'public',
                    'value' => ['type' => DumpValueTypeEnum::String->value, 'value' => 'Laravel Framework Book'],
                ],
                'price' => [
                    'visibility' => 'public',
                    'value' => ['type' => DumpValueTypeEnum::Number->value, 'value' => 49.99],
                ],
                'inStock' => [
                    'visibility' => 'public',
                    'value' => ['type' => DumpValueTypeEnum::Constant->value, 'value' => true],
                ],
                'tags' => [
                    'visibility' => 'public',
                    'value' => [
                        'type' => DumpValueTypeEnum::Array->value,
                        'value' => [
                            'items' => [
                                0 => ['type' => DumpValueTypeEnum::String->value, 'value' => 'php'],
                                1 => ['type' => DumpValueTypeEnum::String->value, 'value' => 'laravel'],
                                2 => ['type' => DumpValueTypeEnum::String->value, 'value' => 'framework'],
                                3 => ['type' => DumpValueTypeEnum::String->value, 'value' => 'web-development'],
                            ],
                            'length' => 4,
                            'numericallyIndexed' => true,
                        ],
                    ],
                ],
                'reviews' => [
                    'visibility' => 'public',
                    'value' => [
                        'type' => DumpValueTypeEnum::Array->value,
                        'value' => [
                            'items' => [
                                [
                                    'type' => DumpValueTypeEnum::Object->value,
                                    'value' => [
                                        'class' => '<runtime object>',
                                        'properties' => [
                                            'id' => [
                                                'visibility' => 'public',
                                                'value' => ['type' => DumpValueTypeEnum::Number->value, 'value' => 1],
                                            ],
                                            'rating' => [
                                                'visibility' => 'public',
                                                'value' => ['type' => DumpValueTypeEnum::Number->value, 'value' => 5],
                                            ],
                                            'comment' => [
                                                'visibility' => 'public',
                                                'value' => ['type' => DumpValueTypeEnum::String->value, 'value' => 'Excellent book!'],
                                            ],
                                            'author' => [
                                                'visibility' => 'public',
                                                'value' => [
                                                    'type' => DumpValueTypeEnum::Object->value,
                                                    'value' => [
                                                        'class' => '<runtime object>',
                                                        'properties' => [
                                                            'id' => [
                                                                'visibility' => 'public',
                                                                'value' => ['type' => DumpValueTypeEnum::Number->value, 'value' => 10],
                                                            ],
                                                            'name' => [
                                                                'visibility' => 'public',
                                                                'value' => ['type' => DumpValueTypeEnum::String->value, 'value' => 'Alice Johnson'],
                                                            ],
                                                        ],
                                                        'propertiesCount' => 2,
                                                    ],
                                                ],
                                            ],
                                        ],
                                        'propertiesCount' => 4,
                                    ],
                                ],
                            ],
                            'length' => 1,
                            'numericallyIndexed' => true,
                        ],
                    ],
                ],
                'bootedCallbacks' => [
                    'visibility' => 'protected',
                    'value' => [
                        'type' => DumpValueTypeEnum::Array->value,
                        'value' => [
                            'items' => [
                                [
                                    'type' => DumpValueTypeEnum::Closure->value,
                                    'value' => [
                                        'signature' => 'Closure($app)',
                                        'class' => 'Illuminate\Filesystem\FilesystemServiceProvider',
                                        'this' => 'Illuminate\Filesystem\FilesystemServiceProvider',
                                    ],
                                ],
                                [
                                    'type' => DumpValueTypeEnum::Closure->value,
                                    'value' => [
                                        'signature' => 'Closure()',
                                        'class' => 'Illuminate\Foundation\Support\Providers\RouteServiceProvider',
                                        'this' => 'Illuminate\Foundation\Support\Providers\RouteServiceProvider',
                                    ],
                                ],
                            ],
                            'length' => 2,
                            'numericallyIndexed' => true,
                        ],
                    ],
                ],
                'environmentResolver' => [
                    'visibility' => 'protected',
                    'value' => [
                        'type' => DumpValueTypeEnum::Closure->value,
                        'value' => [
                            'signature' => 'Illuminate\\Foundation\\Application::environment(...$environments)',
                            'class' => null,
                            'this' => 'Illuminate\Foundation\Application',
                        ],
                    ],
                ],
            ],
        ];

        yield 'eloquent model with attributes' => [
            'html' => <<<'HTML'
<script> Sfdump = window.Sfdump || (function (doc) { doc.documentElement.classList.add('sf-js-enabled'); var rxEsc = /([.*+?^${}()|\[\]\/\\])/g, idRx = /\bsf-dump-\d+-ref[012]\w+\b/, keyHint = 0 <= navigator.platform.toUpperCase().indexOf('MAC') ? 'Cmd' : 'Ctrl', addEventListener = function (e, n, cb) { e.addEventListener(n, cb, false); }; if (!doc.addEventListener) { addEventListener = function (element, eventName, callback) { element.attachEvent('on' + eventName, function (e) { e.preventDefault = function () {e.returnValue = false;}; e.target = e.srcElement; callback(e); }); }; } function toggle(a, recursive) { var s = a.nextSibling || {}, oldClass = s.className, arrow, newClass; if (/\bsf-dump-compact\b/.test(oldClass)) { arrow = '&#9660;'; newClass = 'sf-dump-expanded'; } else if (/\bsf-dump-expanded\b/.test(oldClass)) { arrow = '&#9654;'; newClass = 'sf-dump-compact'; } else { return false; } if (doc.createEvent && s.dispatchEvent) { var event = doc.createEvent('Event'); event.initEvent('sf-dump-expanded' === newClass ? 'sfbeforedumpexpand' : 'sfbeforedumpcollapse', true, false); s.dispatchEvent(event); } a.lastChild.innerHTML = arrow; s.className = s.className.replace(/\bsf-dump-(compact|expanded)\b/, newClass); if (recursive) { try { a = s.querySelectorAll('.'+oldClass); for (s = 0; s < a.length; ++s) { if (-1 == a[s].className.indexOf(newClass)) { a[s].className = newClass; a[s].previousSibling.lastChild.innerHTML = arrow; } } } catch (e) { } } return true; }; function collapse(a, recursive) { var s = a.nextSibling || {}, oldClass = s.className; if (/\bsf-dump-expanded\b/.test(oldClass)) { toggle(a, recursive); return true; } return false; }; function expand(a, recursive) { var s = a.nextSibling || {}, oldClass = s.className; if (/\bsf-dump-compact\b/.test(oldClass)) { toggle(a, recursive); return true; } return false; }; function collapseAll(root) { var a = root.querySelector('a.sf-dump-toggle'); if (a) { collapse(a, true); expand(a); return true; } return false; } function reveal(node) { var previous, parents = []; while ((node = node.parentNode || {}) && (previous = node.previousSibling) && 'A' === previous.tagName) { parents.push(previous); } if (0 !== parents.length) { parents.forEach(function (parent) { expand(parent); }); return true; } return false; } function highlight(root, activeNode, nodes) { resetHighlightedNodes(root); Array.from(nodes||[]).forEach(function (node) { if (!/\bsf-dump-highlight\b/.test(node.className)) { node.className = node.className + ' sf-dump-highlight'; } }); if (!/\bsf-dump-highlight-active\b/.test(activeNode.className)) { activeNode.className = activeNode.className + ' sf-dump-highlight-active'; } } function resetHighlightedNodes(root) { Array.from(root.querySelectorAll('.sf-dump-str, .sf-dump-key, .sf-dump-public, .sf-dump-protected, .sf-dump-private')).forEach(function (strNode) { strNode.className = strNode.className.replace(/\bsf-dump-highlight\b/, ''); strNode.className = strNode.className.replace(/\bsf-dump-highlight-active\b/, ''); }); } return function (root, x) { root = doc.getElementById(root); var indentRx = new RegExp('^('+(root.getAttribute('data-indent-pad') || ' ').replace(rxEsc, '\\$1')+')+', 'm'), options = {"maxDepth":1,"maxStringLength":160,"fileLinkFormat":false}, elt = root.getElementsByTagName('A'), len = elt.length, i = 0, s, h, t = []; while (i < len) t.push(elt[i++]); for (i in x) { options[i] = x[i]; } function a(e, f) { addEventListener(root, e, function (e, n) { if ('A' == e.target.tagName) { f(e.target, e); } else if ('A' == e.target.parentNode.tagName) { f(e.target.parentNode, e); } else { n = /\bsf-dump-ellipsis\b/.test(e.target.className) ? e.target.parentNode : e.target; if ((n = n.nextElementSibling) && 'A' == n.tagName) { if (!/\bsf-dump-toggle\b/.test(n.className)) { n = n.nextElementSibling || n; } f(n, e, true); } } }); }; function isCtrlKey(e) { return e.ctrlKey || e.metaKey; } function xpathString(str) { var parts = str.match(/[^'"]+|['"]/g).map(function (part) { if ("'" == part) { return '"\'"'; } if ('"' == part) { return "'\"'"; } return "'" + part + "'"; }); return "concat(" + parts.join(",") + ", '')"; } function xpathHasClass(className) { return "contains(concat(' ', normalize-space(@class), ' '), ' " + className +" ')"; } a('mouseover', function (a, e, c) { if (c) { e.target.style.cursor = "pointer"; } }); a('click', function (a, e, c) { if (/\bsf-dump-toggle\b/.test(a.className)) { e.preventDefault(); if (!toggle(a, isCtrlKey(e))) { var r = doc.getElementById(a.getAttribute('href').slice(1)), s = r.previousSibling, f = r.parentNode, t = a.parentNode; t.replaceChild(r, a); f.replaceChild(a, s); t.insertBefore(s, r); f = f.firstChild.nodeValue.match(indentRx); t = t.firstChild.nodeValue.match(indentRx); if (f && t && f[0] !== t[0]) { r.innerHTML = r.innerHTML.replace(new RegExp('^'+f[0].replace(rxEsc, '\\$1'), 'mg'), t[0]); } if (/\bsf-dump-compact\b/.test(r.className)) { toggle(s, isCtrlKey(e)); } } if (c) { } else if (doc.getSelection) { try { doc.getSelection().removeAllRanges(); } catch (e) { doc.getSelection().empty(); } } else { doc.selection.empty(); } } else if (/\bsf-dump-str-toggle\b/.test(a.className)) { e.preventDefault(); e = a.parentNode.parentNode; e.className = e.className.replace(/\bsf-dump-str-(expand|collapse)\b/, a.parentNode.className); } }); elt = root.getElementsByTagName('SAMP'); len = elt.length; i = 0; while (i < len) t.push(elt[i++]); len = t.length; for (i = 0; i < len; ++i) { elt = t[i]; if ('SAMP' == elt.tagName) { a = elt.previousSibling || {}; if ('A' != a.tagName) { a = doc.createElement('A'); a.className = 'sf-dump-ref'; elt.parentNode.insertBefore(a, elt); } else { a.innerHTML += ' '; } a.title = (a.title ? a.title+'\n[' : '[')+keyHint+'+click] Expand all children'; a.innerHTML += elt.className == 'sf-dump-compact' ? '<span>&#9654;</span>' : '<span>&#9660;</span>'; a.className += ' sf-dump-toggle'; x = 1; if ('sf-dump' != elt.parentNode.className) { x += elt.parentNode.getAttribute('data-depth')/1; } } else if (/\bsf-dump-ref\b/.test(elt.className) && (a = elt.getAttribute('href'))) { a = a.slice(1); elt.className += ' sf-dump-hover'; elt.className += ' '+a; if (/[\[{]$/.test(elt.previousSibling.nodeValue)) { a = a != elt.nextSibling.id && doc.getElementById(a); try { s = a.nextSibling; elt.appendChild(a); s.parentNode.insertBefore(a, s); if (/^[@#]/.test(elt.innerHTML)) { elt.innerHTML += ' <span>&#9654;</span>'; } else { elt.innerHTML = '<span>&#9654;</span>'; elt.className = 'sf-dump-ref'; } elt.className += ' sf-dump-toggle'; } catch (e) { if ('&' == elt.innerHTML.charAt(0)) { elt.innerHTML = '&#8230;'; elt.className = 'sf-dump-ref'; } } } } } if (doc.evaluate && Array.from && root.children.length > 1) { root.setAttribute('tabindex', 0); SearchState = function () { this.nodes = []; this.idx = 0; }; SearchState.prototype = { next: function () { if (this.isEmpty()) { return this.current(); } this.idx = this.idx < (this.nodes.length - 1) ? this.idx + 1 : 0; return this.current(); }, previous: function () { if (this.isEmpty()) { return this.current(); } this.idx = this.idx > 0 ? this.idx - 1 : (this.nodes.length - 1); return this.current(); }, isEmpty: function () { return 0 === this.count(); }, current: function () { if (this.isEmpty()) { return null; } return this.nodes[this.idx]; }, reset: function () { this.nodes = []; this.idx = 0; }, count: function () { return this.nodes.length; }, }; function showCurrent(state) { var currentNode = state.current(), currentRect, searchRect; if (currentNode) { reveal(currentNode); highlight(root, currentNode, state.nodes); if ('scrollIntoView' in currentNode) { currentNode.scrollIntoView(true); currentRect = currentNode.getBoundingClientRect(); searchRect = search.getBoundingClientRect(); if (currentRect.top < (searchRect.top + searchRect.height)) { window.scrollBy(0, -(searchRect.top + searchRect.height + 5)); } } } counter.textContent = (state.isEmpty() ? 0 : state.idx + 1) + ' of ' + state.count(); } var search = doc.createElement('div'); search.className = 'sf-dump-search-wrapper sf-dump-search-hidden'; search.innerHTML = ' <input type="text" class="sf-dump-search-input"> <span class="sf-dump-search-count">0 of 0<\/span> <button type="button" class="sf-dump-search-input-previous" tabindex="-1"> <svg viewBox="0 0 1792 1792" xmlns="http://www.w3.org/2000/svg"><path d="M1683 1331l-166 165q-19 19-45 19t-45-19L896 965l-531 531q-19 19-45 19t-45-19l-166-165q-19-19-19-45.5t19-45.5l742-741q19-19 45-19t45 19l742 741q19 19 19 45.5t-19 45.5z"\/><\/svg> <\/button> <button type="button" class="sf-dump-search-input-next" tabindex="-1"> <svg viewBox="0 0 1792 1792" xmlns="http://www.w3.org/2000/svg"><path d="M1683 808l-742 741q-19 19-45 19t-45-19L109 808q-19-19-19-45.5t19-45.5l166-165q19-19 45-19t45 19l531 531 531-531q19-19 45-19t45 19l166 165q19 19 19 45.5t-19 45.5z"\/><\/svg> <\/button> '; root.insertBefore(search, root.firstChild); var state = new SearchState(); var searchInput = search.querySelector('.sf-dump-search-input'); var counter = search.querySelector('.sf-dump-search-count'); var searchInputTimer = 0; var previousSearchQuery = ''; addEventListener(searchInput, 'keyup', function (e) { var searchQuery = e.target.value; /* Don't perform anything if the pressed key didn't change the query */ if (searchQuery === previousSearchQuery) { return; } previousSearchQuery = searchQuery; clearTimeout(searchInputTimer); searchInputTimer = setTimeout(function () { state.reset(); collapseAll(root); resetHighlightedNodes(root); if ('' === searchQuery) { counter.textContent = '0 of 0'; return; } var classMatches = [ "sf-dump-str", "sf-dump-key", "sf-dump-public", "sf-dump-protected", "sf-dump-private", ].map(xpathHasClass).join(' or '); var xpathResult = doc.evaluate('.//span[' + classMatches + '][contains(translate(child::text(), ' + xpathString(searchQuery.toUpperCase()) + ', ' + xpathString(searchQuery.toLowerCase()) + '), ' + xpathString(searchQuery.toLowerCase()) + ')]', root, null, XPathResult.ORDERED_NODE_ITERATOR_TYPE, null); while (node = xpathResult.iterateNext()) state.nodes.push(node); showCurrent(state); }, 400); }); Array.from(search.querySelectorAll('.sf-dump-search-input-next, .sf-dump-search-input-previous')).forEach(function (btn) { addEventListener(btn, 'click', function (e) { e.preventDefault(); -1 !== e.target.className.indexOf('next') ? state.next() : state.previous(); searchInput.focus(); collapseAll(root); showCurrent(state); }) }); addEventListener(root, 'keydown', function (e) { var isSearchActive = !/\bsf-dump-search-hidden\b/.test(search.className); if ((114 === e.keyCode && !isSearchActive) || (isCtrlKey(e) && 70 === e.keyCode)) { /* F3 or CMD/CTRL + F */ if (70 === e.keyCode && document.activeElement === searchInput) { /* * If CMD/CTRL + F is hit while having focus on search input, * the user probably meant to trigger browser search instead. * Let the browser execute its behavior: */ return; } e.preventDefault(); search.className = search.className.replace(/\bsf-dump-search-hidden\b/, ''); searchInput.focus(); } else if (isSearchActive) { if (27 === e.keyCode) { /* ESC key */ search.className += ' sf-dump-search-hidden'; e.preventDefault(); resetHighlightedNodes(root); searchInput.value = ''; } else if ( (isCtrlKey(e) && 71 === e.keyCode) /* CMD/CTRL + G */ || 13 === e.keyCode /* Enter */ || 114 === e.keyCode /* F3 */ ) { e.preventDefault(); e.shiftKey ? state.previous() : state.next(); collapseAll(root); showCurrent(state); } } }); } if (0 >= options.maxStringLength) { return; } try { elt = root.querySelectorAll('.sf-dump-str'); len = elt.length; i = 0; t = []; while (i < len) t.push(elt[i++]); len = t.length; for (i = 0; i < len; ++i) { elt = t[i]; s = elt.innerText || elt.textContent; x = s.length - options.maxStringLength; if (0 < x) { h = elt.innerHTML; elt[elt.innerText ? 'innerText' : 'textContent'] = s.substring(0, options.maxStringLength); elt.className += ' sf-dump-str-collapse'; elt.innerHTML = '<span class=sf-dump-str-collapse>'+h+'<a class="sf-dump-ref sf-dump-str-toggle" title="Collapse"> &#9664;</a></span>'+ '<span class=sf-dump-str-expand>'+elt.innerHTML+'<a class="sf-dump-ref sf-dump-str-toggle" title="'+x+' remaining characters"> &#9654;</a></span>'; } } } catch (e) { } }; })(document); </script><style> .sf-js-enabled pre.sf-dump .sf-dump-compact, .sf-js-enabled .sf-dump-str-collapse .sf-dump-str-collapse, .sf-js-enabled .sf-dump-str-expand .sf-dump-str-expand { display: none; } .sf-dump-hover:hover { background-color: #B729D9; color: #FFF !important; border-radius: 2px; } pre.sf-dump { display: block; white-space: pre; padding: 5px; overflow: initial !important; } pre.sf-dump:after { content: ""; visibility: hidden; display: block; height: 0; clear: both; } pre.sf-dump .sf-dump-ellipsization { display: inline-flex; } pre.sf-dump a { text-decoration: none; cursor: pointer; border: 0; outline: none; color: inherit; } pre.sf-dump img { max-width: 50em; max-height: 50em; margin: .5em 0 0 0; padding: 0; background: url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAAAAAA6mKC9AAAAHUlEQVQY02O8zAABilCaiQEN0EeA8QuUcX9g3QEAAjcC5piyhyEAAAAASUVORK5CYII=) #D3D3D3; } pre.sf-dump .sf-dump-ellipsis { text-overflow: ellipsis; white-space: nowrap; overflow: hidden; } pre.sf-dump .sf-dump-ellipsis-tail { flex-shrink: 0; } pre.sf-dump code { display:inline; padding:0; background:none; } .sf-dump-public.sf-dump-highlight, .sf-dump-protected.sf-dump-highlight, .sf-dump-private.sf-dump-highlight, .sf-dump-str.sf-dump-highlight, .sf-dump-key.sf-dump-highlight { background: rgba(111, 172, 204, 0.3); border: 1px solid #7DA0B1; border-radius: 3px; } .sf-dump-public.sf-dump-highlight-active, .sf-dump-protected.sf-dump-highlight-active, .sf-dump-private.sf-dump-highlight-active, .sf-dump-str.sf-dump-highlight-active, .sf-dump-key.sf-dump-highlight-active { background: rgba(253, 175, 0, 0.4); border: 1px solid #ffa500; border-radius: 3px; } pre.sf-dump .sf-dump-search-hidden { display: none !important; } pre.sf-dump .sf-dump-search-wrapper { font-size: 0; white-space: nowrap; margin-bottom: 5px; display: flex; position: -webkit-sticky; position: sticky; top: 5px; } pre.sf-dump .sf-dump-search-wrapper > * { vertical-align: top; box-sizing: border-box; height: 21px; font-weight: normal; border-radius: 0; background: #FFF; color: #757575; border: 1px solid #BBB; } pre.sf-dump .sf-dump-search-wrapper > input.sf-dump-search-input { padding: 3px; height: 21px; font-size: 12px; border-right: none; border-top-left-radius: 3px; border-bottom-left-radius: 3px; color: #000; min-width: 15px; width: 100%; } pre.sf-dump .sf-dump-search-wrapper > .sf-dump-search-input-next, pre.sf-dump .sf-dump-search-wrapper > .sf-dump-search-input-previous { background: #F2F2F2; outline: none; border-left: none; font-size: 0; line-height: 0; } pre.sf-dump .sf-dump-search-wrapper > .sf-dump-search-input-next { border-top-right-radius: 3px; border-bottom-right-radius: 3px; } pre.sf-dump .sf-dump-search-wrapper > .sf-dump-search-input-next > svg, pre.sf-dump .sf-dump-search-wrapper > .sf-dump-search-input-previous > svg { pointer-events: none; width: 12px; height: 12px; } pre.sf-dump .sf-dump-search-wrapper > .sf-dump-search-count { display: inline-block; padding: 0 5px; margin: 0; border-left: none; line-height: 21px; font-size: 12px; }pre.sf-dump, pre.sf-dump .sf-dump-default{background-color:#18171B; color:#FF8400; line-height:1.2em; font:12px Menlo, Monaco, Consolas, monospace; word-wrap: break-word; white-space: pre-wrap; position:relative; z-index:99999; word-break: break-all}pre.sf-dump .sf-dump-num{font-weight:bold; color:#1299DA}pre.sf-dump .sf-dump-const{font-weight:bold}pre.sf-dump .sf-dump-virtual{font-style:italic}pre.sf-dump .sf-dump-str{font-weight:bold; color:#56DB3A}pre.sf-dump .sf-dump-note{color:#1299DA}pre.sf-dump .sf-dump-ref{color:#A0A0A0}pre.sf-dump .sf-dump-public{color:#FFFFFF}pre.sf-dump .sf-dump-protected{color:#FFFFFF}pre.sf-dump .sf-dump-private{color:#FFFFFF}pre.sf-dump .sf-dump-meta{color:#B729D9}pre.sf-dump .sf-dump-key{color:#56DB3A}pre.sf-dump .sf-dump-index{color:#1299DA}pre.sf-dump .sf-dump-ellipsis{color:#FF8400}pre.sf-dump .sf-dump-ns{user-select:none;}pre.sf-dump .sf-dump-ellipsis-note{color:#1299DA}</style><pre class=sf-dump id=sf-dump-1250988803 data-indent-pad="  "><span class=sf-dump-note>App\Models\User</span> {<a class=sf-dump-ref>#480</a><samp data-depth=1 class=sf-dump-expanded><span style="color: #A0A0A0;"> // app/Http/Controllers/Demo/DumpAndDieController.php:140</span>
  #<span class=sf-dump-protected title="Protected property">connection</span>: <span class=sf-dump-const>null</span>
  #<span class=sf-dump-protected title="Protected property">table</span>: <span class=sf-dump-const>null</span>
  #<span class=sf-dump-protected title="Protected property">primaryKey</span>: "<span class=sf-dump-str title="2 characters">id</span>"
  #<span class=sf-dump-protected title="Protected property">keyType</span>: "<span class=sf-dump-str title="3 characters">int</span>"
  +<span class=sf-dump-public title="Public property">incrementing</span>: <span class=sf-dump-const>true</span>
  #<span class=sf-dump-protected title="Protected property">with</span>: []
  #<span class=sf-dump-protected title="Protected property">withCount</span>: []
  +<span class=sf-dump-public title="Public property">preventsLazyLoading</span>: <span class=sf-dump-const>false</span>
  #<span class=sf-dump-protected title="Protected property">perPage</span>: <span class=sf-dump-num>15</span>
  +<span class=sf-dump-public title="Public property">exists</span>: <span class=sf-dump-const>false</span>
  +<span class=sf-dump-public title="Public property">wasRecentlyCreated</span>: <span class=sf-dump-const>false</span>
  #<span class=sf-dump-protected title="Protected property">escapeWhenCastingToString</span>: <span class=sf-dump-const>false</span>
  #<span class=sf-dump-protected title="Protected property">attributes</span>: <span class=sf-dump-note>array:9</span> [<samp data-depth=2 class=sf-dump-compact>
    "<span class=sf-dump-key>name</span>" => "<span class=sf-dump-str title="12 characters">Okey Hackett</span>"
    "<span class=sf-dump-key>email</span>" => "<span class=sf-dump-str title="19 characters">cyril82@example.org</span>"
    "<span class=sf-dump-key>email_verified_at</span>" => "<span class=sf-dump-str title="19 characters">2025-12-31 19:38:53</span>"
    "<span class=sf-dump-key>password</span>" => "<span class=sf-dump-str title="60 characters">$2y$12$LKEB3zRaXrpVXYnxMps1HOs0Y.mndqde.13jPXGNj.bR9rNNfvbtO</span>"
    "<span class=sf-dump-key>remember_token</span>" => "<span class=sf-dump-str title="10 characters">ulEFK1uVWV</span>"
    "<span class=sf-dump-key>two_factor_secret</span>" => "<span class=sf-dump-str title="10 characters">CQzbuReqy8</span>"
    "<span class=sf-dump-key>two_factor_recovery_codes</span>" => "<span class=sf-dump-str title="10 characters">ie8xJ6Cgmi</span>"
    "<span class=sf-dump-key>two_factor_confirmed_at</span>" => <span class="sf-dump-note sf-dump-ellipsization" title="Illuminate\Support\Carbon @1767209933
"><span class="sf-dump-ellipsis sf-dump-ellipsis-note">Illuminate\Support</span><span class="sf-dump-ellipsis sf-dump-ellipsis-note">\</span><span class="sf-dump-ellipsis-tail">Carbon @1767209933</span></span> {<a class=sf-dump-ref>#478</a><samp data-depth=3 class=sf-dump-compact>
      #<span class=sf-dump-protected title="Protected property">endOfTime</span>: <span class=sf-dump-const>false</span>
      #<span class=sf-dump-protected title="Protected property">startOfTime</span>: <span class=sf-dump-const>false</span>
      #<span class=sf-dump-protected title="Protected property">constructedObjectId</span>: "<span class=sf-dump-str title="32 characters">00000000000001de0000000000000000</span>"
      -<span class=sf-dump-private title="Private property defined in class:&#10;`Carbon\Carbon`">clock</span>: <span class=sf-dump-const>null</span>
      #<span class=sf-dump-protected title="Protected property">localMonthsOverflow</span>: <span class=sf-dump-const>null</span>
      #<span class=sf-dump-protected title="Protected property">localYearsOverflow</span>: <span class=sf-dump-const>null</span>
      #<span class=sf-dump-protected title="Protected property">localStrictModeEnabled</span>: <span class=sf-dump-const>null</span>
      #<span class=sf-dump-protected title="Protected property">localHumanDiffOptions</span>: <span class=sf-dump-const>null</span>
      #<span class=sf-dump-protected title="Protected property">localToStringFormat</span>: <span class=sf-dump-const>null</span>
      #<span class=sf-dump-protected title="Protected property">localSerializer</span>: <span class=sf-dump-const>null</span>
      #<span class=sf-dump-protected title="Protected property">localMacros</span>: <span class=sf-dump-const>null</span>
      #<span class=sf-dump-protected title="Protected property">localGenericMacros</span>: <span class=sf-dump-const>null</span>
      #<span class=sf-dump-protected title="Protected property">localFormatFunction</span>: <span class=sf-dump-const>null</span>
      #<span class=sf-dump-protected title="Protected property">localTranslator</span>: <span class=sf-dump-const>null</span>
      #<span class=sf-dump-protected title="Protected property">dumpProperties</span>: <span class=sf-dump-note>array:3</span> [<samp data-depth=4 class=sf-dump-compact>
        <span class=sf-dump-index>0</span> => "<span class=sf-dump-str title="4 characters">date</span>"
        <span class=sf-dump-index>1</span> => "<span class=sf-dump-str title="13 characters">timezone_type</span>"
        <span class=sf-dump-index>2</span> => "<span class=sf-dump-str title="8 characters">timezone</span>"
      </samp>]
      #<span class=sf-dump-protected title="Protected property">dumpLocale</span>: <span class=sf-dump-const>null</span>
      #<span class=sf-dump-protected title="Protected property">dumpDateProperties</span>: <span class=sf-dump-const>null</span>
      <span class=sf-dump-meta>date</span>: <span class=sf-dump-const title="Wednesday, December 31, 2025
- 00:00:00.000821 from now
DST Off">2025-12-31 19:38:53.558338 UTC (+00:00)</span>
    </samp>}
    "<span class=sf-dump-key>emptyObject</span>" => {<a class=sf-dump-ref>#437</a>}
  </samp>]
  #<span class=sf-dump-protected title="Protected property">original</span>: []
  #<span class=sf-dump-protected title="Protected property">changes</span>: []
  #<span class=sf-dump-protected title="Protected property">previous</span>: []
  #<span class=sf-dump-protected title="Protected property">casts</span>: <span class=sf-dump-note>array:3</span> [<samp data-depth=2 class=sf-dump-compact>
    "<span class=sf-dump-key>email_verified_at</span>" => "<span class=sf-dump-str title="8 characters">datetime</span>"
    "<span class=sf-dump-key>password</span>" => "<span class=sf-dump-str title="6 characters">hashed</span>"
    "<span class=sf-dump-key>role</span>" => "<span class=sf-dump-str title="22 characters">App\Models\ExampleEnum</span>"
  </samp>]
  #<span class=sf-dump-protected title="Protected property">classCastCache</span>: []
  #<span class=sf-dump-protected title="Protected property">attributeCastCache</span>: []
  #<span class=sf-dump-protected title="Protected property">dateFormat</span>: <span class=sf-dump-const>null</span>
  #<span class=sf-dump-protected title="Protected property">appends</span>: []
  #<span class=sf-dump-protected title="Protected property">dispatchesEvents</span>: []
  #<span class=sf-dump-protected title="Protected property">observables</span>: []
  #<span class=sf-dump-protected title="Protected property">relations</span>: []
  #<span class=sf-dump-protected title="Protected property">touches</span>: []
  #<span class=sf-dump-protected title="Protected property">relationAutoloadCallback</span>: <span class=sf-dump-const>null</span>
  #<span class=sf-dump-protected title="Protected property">relationAutoloadContext</span>: <span class=sf-dump-const>null</span>
  +<span class=sf-dump-public title="Public property">timestamps</span>: <span class=sf-dump-const>true</span>
  +<span class=sf-dump-public title="Public property">usesUniqueIds</span>: <span class=sf-dump-const>false</span>
  #<span class=sf-dump-protected title="Protected property">hidden</span>: <span class=sf-dump-note>array:2</span> [<samp data-depth=2 class=sf-dump-compact>
    <span class=sf-dump-index>0</span> => "<span class=sf-dump-str title="8 characters">password</span>"
    <span class=sf-dump-index>1</span> => "<span class=sf-dump-str title="14 characters">remember_token</span>"
  </samp>]
  #<span class=sf-dump-protected title="Protected property">visible</span>: []
  #<span class=sf-dump-protected title="Protected property">fillable</span>: <span class=sf-dump-note>array:3</span> [<samp data-depth=2 class=sf-dump-compact>
    <span class=sf-dump-index>0</span> => "<span class=sf-dump-str title="4 characters">name</span>"
    <span class=sf-dump-index>1</span> => "<span class=sf-dump-str title="5 characters">email</span>"
    <span class=sf-dump-index>2</span> => "<span class=sf-dump-str title="8 characters">password</span>"
  </samp>]
  #<span class=sf-dump-protected title="Protected property">guarded</span>: <span class=sf-dump-note>array:1</span> [<samp data-depth=2 class=sf-dump-compact>
    <span class=sf-dump-index>0</span> => "<span class=sf-dump-str>*</span>"
  </samp>]
  #<span class=sf-dump-protected title="Protected property">authPasswordName</span>: "<span class=sf-dump-str title="8 characters">password</span>"
  #<span class=sf-dump-protected title="Protected property">rememberTokenName</span>: "<span class=sf-dump-str title="14 characters">remember_token</span>"
  -<span class=sf-dump-private title="Private property defined in class:&#10;`App\Models\User`">example</span>: "<span class=sf-dump-str title="3 characters">wow</span>"
</samp>}
</pre><script>Sfdump("sf-dump-1250988803")</script>
HTML,
            'expectedClass' => 'App\Models\User',
            'expectedProperties' => [
                'connection' => [
                    'visibility' => 'protected',
                    'value' => ['type' => DumpValueTypeEnum::Constant->value, 'value' => null],
                ],
                'table' => [
                    'visibility' => 'protected',
                    'value' => ['type' => DumpValueTypeEnum::Constant->value, 'value' => null],
                ],
                'primaryKey' => [
                    'visibility' => 'protected',
                    'value' => ['type' => DumpValueTypeEnum::String->value, 'value' => 'id'],
                ],
                'keyType' => [
                    'visibility' => 'protected',
                    'value' => ['type' => DumpValueTypeEnum::String->value, 'value' => 'int'],
                ],
                'incrementing' => [
                    'visibility' => 'public',
                    'value' => ['type' => DumpValueTypeEnum::Constant->value, 'value' => true],
                ],
                'with' => [
                    'visibility' => 'protected',
                    'value' => [
                        'type' => DumpValueTypeEnum::Array->value,
                        'value' => [
                            'items' => [],
                            'length' => 0,
                            'numericallyIndexed' => true,
                        ],
                    ],
                ],
                'withCount' => [
                    'visibility' => 'protected',
                    'value' => [
                        'type' => DumpValueTypeEnum::Array->value,
                        'value' => [
                            'items' => [],
                            'length' => 0,
                            'numericallyIndexed' => true,
                        ],
                    ],
                ],
                'preventsLazyLoading' => [
                    'visibility' => 'public',
                    'value' => ['type' => DumpValueTypeEnum::Constant->value, 'value' => false],
                ],
                'perPage' => [
                    'visibility' => 'protected',
                    'value' => ['type' => DumpValueTypeEnum::Number->value, 'value' => 15],
                ],
                'exists' => [
                    'visibility' => 'public',
                    'value' => ['type' => DumpValueTypeEnum::Constant->value, 'value' => false],
                ],
                'wasRecentlyCreated' => [
                    'visibility' => 'public',
                    'value' => ['type' => DumpValueTypeEnum::Constant->value, 'value' => false],
                ],
                'escapeWhenCastingToString' => [
                    'visibility' => 'protected',
                    'value' => ['type' => DumpValueTypeEnum::Constant->value, 'value' => false],
                ],
                'attributes' => [
                    'visibility' => 'protected',
                    'value' => [
                        'type' => DumpValueTypeEnum::Array->value,
                        'value' => [
                            'length' => 9,
                            'items' => [
                                'name' => [
                                    'type' => DumpValueTypeEnum::String->value,
                                    'value' => 'Okey Hackett',
                                ],
                                'email' => [
                                    'type' => DumpValueTypeEnum::String->value,
                                    'value' => 'cyril82@example.org',
                                ],
                                'email_verified_at' => [
                                    'type' => DumpValueTypeEnum::String->value,
                                    'value' => '2025-12-31 19:38:53',
                                ],
                                'password' => [
                                    'type' => DumpValueTypeEnum::String->value,
                                    'value' => '$2y$12$LKEB3zRaXrpVXYnxMps1HOs0Y.mndqde.13jPXGNj.bR9rNNfvbtO',
                                ],
                                'remember_token' => [
                                    'type' => DumpValueTypeEnum::String->value,
                                    'value' => 'ulEFK1uVWV',
                                ],
                                'two_factor_secret' => [
                                    'type' => DumpValueTypeEnum::String->value,
                                    'value' => 'CQzbuReqy8',
                                ],
                                'two_factor_recovery_codes' => [
                                    'type' => DumpValueTypeEnum::String->value,
                                    'value' => 'ie8xJ6Cgmi',
                                ],
                                'two_factor_confirmed_at' => [
                                    'type' => DumpValueTypeEnum::Object->value,
                                    'value' => [
                                        'class' => 'Illuminate\\Support\\Carbon',
                                        'properties' => [
                                            'clock' => [
                                                'value' => [
                                                    'type' => DumpValueTypeEnum::Constant->value,
                                                    'value' => null,
                                                ],
                                                'visibility' => 'private',
                                            ],
                                            'constructedObjectId' => [
                                                'value' => [
                                                    'type' => DumpValueTypeEnum::String->value,
                                                    'value' => '00000000000001de0000000000000000',
                                                ],
                                                'visibility' => 'protected',
                                            ],
                                            'dumpDateProperties' => [
                                                'value' => [
                                                    'type' => DumpValueTypeEnum::Constant->value,
                                                    'value' => null,
                                                ],
                                                'visibility' => 'protected',
                                            ],
                                            'dumpLocale' => [
                                                'value' => [
                                                    'type' => DumpValueTypeEnum::Constant->value,
                                                    'value' => null,
                                                ],
                                                'visibility' => 'protected',
                                            ],
                                            'dumpProperties' => [
                                                'value' => [
                                                    'type' => DumpValueTypeEnum::Array->value,
                                                    'value' => [
                                                        'items' => [
                                                            ['type' => DumpValueTypeEnum::String->value, 'value' => 'date'],
                                                            ['type' => DumpValueTypeEnum::String->value, 'value' => 'timezone_type'],
                                                            ['type' => DumpValueTypeEnum::String->value, 'value' => 'timezone'],
                                                        ],
                                                        'length' => 3,
                                                        'numericallyIndexed' => true,
                                                    ],
                                                ],
                                                'visibility' => 'protected',
                                            ],
                                            'endOfTime' => [
                                                'value' => [
                                                    'type' => DumpValueTypeEnum::Constant->value,
                                                    'value' => false,
                                                ],
                                                'visibility' => 'protected',
                                            ],
                                            'localFormatFunction' => [
                                                'value' => [
                                                    'type' => DumpValueTypeEnum::Constant->value,
                                                    'value' => null,
                                                ],
                                                'visibility' => 'protected',
                                            ],
                                            'localGenericMacros' => [
                                                'value' => [
                                                    'type' => DumpValueTypeEnum::Constant->value,
                                                    'value' => null,
                                                ],
                                                'visibility' => 'protected',
                                            ],
                                            'localHumanDiffOptions' => [
                                                'value' => [
                                                    'type' => DumpValueTypeEnum::Constant->value,
                                                    'value' => null,
                                                ],
                                                'visibility' => 'protected',
                                            ],
                                            'localMacros' => [
                                                'value' => [
                                                    'type' => DumpValueTypeEnum::Constant->value,
                                                    'value' => null,
                                                ],
                                                'visibility' => 'protected',
                                            ],
                                            'localMonthsOverflow' => [
                                                'value' => [
                                                    'type' => DumpValueTypeEnum::Constant->value,
                                                    'value' => null,
                                                ],
                                                'visibility' => 'protected',
                                            ],
                                            'localSerializer' => [
                                                'value' => [
                                                    'type' => DumpValueTypeEnum::Constant->value,
                                                    'value' => null,
                                                ],
                                                'visibility' => 'protected',
                                            ],
                                            'localStrictModeEnabled' => [
                                                'value' => [
                                                    'type' => DumpValueTypeEnum::Constant->value,
                                                    'value' => null,
                                                ],
                                                'visibility' => 'protected',
                                            ],
                                            'localToStringFormat' => [
                                                'value' => [
                                                    'type' => DumpValueTypeEnum::Constant->value,
                                                    'value' => null,
                                                ],
                                                'visibility' => 'protected',
                                            ],
                                            'localTranslator' => [
                                                'value' => [
                                                    'type' => DumpValueTypeEnum::Constant->value,
                                                    'value' => null,
                                                ],
                                                'visibility' => 'protected',
                                            ],
                                            'localYearsOverflow' => [
                                                'value' => [
                                                    'type' => DumpValueTypeEnum::Constant->value,
                                                    'value' => null,
                                                ],
                                                'visibility' => 'protected',
                                            ],
                                            'startOfTime' => [
                                                'value' => [
                                                    'type' => DumpValueTypeEnum::Constant->value,
                                                    'value' => false,
                                                ],
                                                'visibility' => 'protected',
                                            ],
                                        ],
                                        'propertiesCount' => 17,
                                    ],
                                ],
                                'emptyObject' => [
                                    'type' => DumpValueTypeEnum::Object->value,
                                    'value' => [
                                        'class' => '<runtime object>',
                                        'properties' => [],
                                        'propertiesCount' => 0,
                                    ],
                                ],
                            ],
                            'numericallyIndexed' => false,
                        ],
                    ],
                ],
                'original' => [
                    'visibility' => 'protected',
                    'value' => [
                        'type' => DumpValueTypeEnum::Array->value,
                        'value' => [
                            'items' => [],
                            'length' => 0,
                            'numericallyIndexed' => true,
                        ],
                    ],
                ],
                'changes' => [
                    'visibility' => 'protected',
                    'value' => [
                        'type' => DumpValueTypeEnum::Array->value,
                        'value' => [
                            'items' => [],
                            'length' => 0,
                            'numericallyIndexed' => true,
                        ],
                    ],
                ],
                'previous' => [
                    'visibility' => 'protected',
                    'value' => [
                        'type' => DumpValueTypeEnum::Array->value,
                        'value' => [
                            'items' => [],
                            'length' => 0,
                            'numericallyIndexed' => true,
                        ],
                    ],
                ],
                'casts' => [
                    'visibility' => 'protected',
                    'value' => [
                        'type' => DumpValueTypeEnum::Array->value,
                        'value' => [
                            'length' => 3,
                            'items' => [
                                'email_verified_at' => [
                                    'type' => DumpValueTypeEnum::String->value,
                                    'value' => 'datetime',
                                ],
                                'password' => [
                                    'type' => DumpValueTypeEnum::String->value,
                                    'value' => 'hashed',
                                ],
                                'role' => [
                                    'type' => DumpValueTypeEnum::String->value,
                                    'value' => 'App\\Models\\ExampleEnum',
                                ],
                            ],
                            'numericallyIndexed' => false,
                        ],
                    ],
                ],
                'classCastCache' => [
                    'visibility' => 'protected',
                    'value' => [
                        'type' => DumpValueTypeEnum::Array->value,
                        'value' => [
                            'items' => [],
                            'length' => 0,
                            'numericallyIndexed' => true,
                        ],
                    ],
                ],
                'attributeCastCache' => [
                    'visibility' => 'protected',
                    'value' => [
                        'type' => DumpValueTypeEnum::Array->value,
                        'value' => [
                            'items' => [],
                            'length' => 0,
                            'numericallyIndexed' => true,
                        ],
                    ],
                ],
                'dateFormat' => [
                    'visibility' => 'protected',
                    'value' => ['type' => DumpValueTypeEnum::Constant->value, 'value' => null],
                ],
                'appends' => [
                    'visibility' => 'protected',
                    'value' => [
                        'type' => DumpValueTypeEnum::Array->value,
                        'value' => [
                            'items' => [],
                            'length' => 0,
                            'numericallyIndexed' => true,
                        ],
                    ],
                ],
                'dispatchesEvents' => [
                    'visibility' => 'protected',
                    'value' => [
                        'type' => DumpValueTypeEnum::Array->value,
                        'value' => [
                            'items' => [],
                            'length' => 0,
                            'numericallyIndexed' => true,
                        ],
                    ],
                ],
                'observables' => [
                    'visibility' => 'protected',
                    'value' => [
                        'type' => DumpValueTypeEnum::Array->value,
                        'value' => [
                            'items' => [],
                            'length' => 0,
                            'numericallyIndexed' => true,
                        ],
                    ],
                ],
                'relations' => [
                    'visibility' => 'protected',
                    'value' => [
                        'type' => DumpValueTypeEnum::Array->value,
                        'value' => [
                            'items' => [],
                            'length' => 0,
                            'numericallyIndexed' => true,
                        ],
                    ],
                ],
                'touches' => [
                    'visibility' => 'protected',
                    'value' => [
                        'type' => DumpValueTypeEnum::Array->value,
                        'value' => [
                            'items' => [],
                            'length' => 0,
                            'numericallyIndexed' => true,
                        ],
                    ],
                ],
                'relationAutoloadCallback' => [
                    'visibility' => 'protected',
                    'value' => ['type' => DumpValueTypeEnum::Constant->value, 'value' => null],
                ],
                'relationAutoloadContext' => [
                    'visibility' => 'protected',
                    'value' => ['type' => DumpValueTypeEnum::Constant->value, 'value' => null],
                ],
                'timestamps' => [
                    'visibility' => 'public',
                    'value' => ['type' => DumpValueTypeEnum::Constant->value, 'value' => true],
                ],
                'usesUniqueIds' => [
                    'visibility' => 'public',
                    'value' => ['type' => DumpValueTypeEnum::Constant->value, 'value' => false],
                ],
                'hidden' => [
                    'visibility' => 'protected',
                    'value' => [
                        'type' => DumpValueTypeEnum::Array->value,
                        'value' => [
                            'length' => 2,
                            'items' => [
                                ['type' => DumpValueTypeEnum::String->value, 'value' => 'password'],
                                ['type' => DumpValueTypeEnum::String->value, 'value' => 'remember_token'],
                            ],
                            'numericallyIndexed' => true,
                        ],
                    ],
                ],
                'visible' => [
                    'visibility' => 'protected',
                    'value' => [
                        'type' => DumpValueTypeEnum::Array->value,
                        'value' => [
                            'items' => [],
                            'length' => 0,
                            'numericallyIndexed' => true,
                        ],
                    ],
                ],
                'fillable' => [
                    'visibility' => 'protected',
                    'value' => [
                        'type' => DumpValueTypeEnum::Array->value,
                        'value' => [
                            'length' => 3,
                            'items' => [
                                ['type' => DumpValueTypeEnum::String->value, 'value' => 'name'],
                                ['type' => DumpValueTypeEnum::String->value, 'value' => 'email'],
                                ['type' => DumpValueTypeEnum::String->value, 'value' => 'password'],
                            ],
                            'numericallyIndexed' => true,
                        ],
                    ],
                ],
                'guarded' => [
                    'visibility' => 'protected',
                    'value' => [
                        'type' => DumpValueTypeEnum::Array->value,
                        'value' => [
                            'length' => 1,
                            'items' => [
                                ['type' => DumpValueTypeEnum::String->value, 'value' => '*'],
                            ],
                            'numericallyIndexed' => true,
                        ],
                    ],
                ],
                'authPasswordName' => [
                    'visibility' => 'protected',
                    'value' => ['type' => DumpValueTypeEnum::String->value, 'value' => 'password'],
                ],
                'rememberTokenName' => [
                    'visibility' => 'protected',
                    'value' => ['type' => DumpValueTypeEnum::String->value, 'value' => 'remember_token'],
                ],
                'example' => [
                    'visibility' => 'private',
                    'value' => ['type' => DumpValueTypeEnum::String->value, 'value' => 'wow'],
                ],
            ],
        ];

        yield 'enum object' => [
            'html' => <<<'HTML'
<pre class=sf-dump><span class=sf-dump-note>App\Enums\UserRole</span> {<a class=sf-dump-ref>#200</a><samp data-depth=1 class=sf-dump-expanded>
  +<span class=sf-dump-public title="Public property">name</span>: "<span class=sf-dump-str title="5 characters">Admin</span>"
  +<span class=sf-dump-public title="Public property">value</span>: "<span class=sf-dump-str title="5 characters">admin</span>"
</samp>}</pre>
HTML,
            'expectedClass' => 'App\Enums\UserRole',
            'expectedProperties' => [
                'name' => [
                    'visibility' => 'public',
                    'value' => ['type' => DumpValueTypeEnum::String->value, 'value' => 'Admin'],
                ],
                'value' => [
                    'visibility' => 'public',
                    'value' => ['type' => DumpValueTypeEnum::String->value, 'value' => 'admin'],
                ],
            ],
        ];

        yield 'laravel request object' => [
            'html' => <<<'HTML'
<script> Sfdump = window.Sfdump || (function (doc) { doc.documentElement.classList.add('sf-js-enabled'); var rxEsc = /([.*+?^${}()|\[\]\/\\])/g, idRx = /\bsf-dump-\d+-ref[012]\w+\b/, keyHint = 0 <= navigator.platform.toUpperCase().indexOf('MAC') ? 'Cmd' : 'Ctrl', addEventListener = function (e, n, cb) { e.addEventListener(n, cb, false); }; if (!doc.addEventListener) { addEventListener = function (element, eventName, callback) { element.attachEvent('on' + eventName, function (e) { e.preventDefault = function () {e.returnValue = false;}; e.target = e.srcElement; callback(e); }); }; } function toggle(a, recursive) { var s = a.nextSibling || {}, oldClass = s.className, arrow, newClass; if (/\bsf-dump-compact\b/.test(oldClass)) { arrow = '&#9660;'; newClass = 'sf-dump-expanded'; } else if (/\bsf-dump-expanded\b/.test(oldClass)) { arrow = '&#9654;'; newClass = 'sf-dump-compact'; } else { return false; } if (doc.createEvent && s.dispatchEvent) { var event = doc.createEvent('Event'); event.initEvent('sf-dump-expanded' === newClass ? 'sfbeforedumpexpand' : 'sfbeforedumpcollapse', true, false); s.dispatchEvent(event); } a.lastChild.innerHTML = arrow; s.className = s.className.replace(/\bsf-dump-(compact|expanded)\b/, newClass); if (recursive) { try { a = s.querySelectorAll('.'+oldClass); for (s = 0; s < a.length; ++s) { if (-1 == a[s].className.indexOf(newClass)) { a[s].className = newClass; a[s].previousSibling.lastChild.innerHTML = arrow; } } } catch (e) { } } return true; }; function collapse(a, recursive) { var s = a.nextSibling || {}, oldClass = s.className; if (/\bsf-dump-expanded\b/.test(oldClass)) { toggle(a, recursive); return true; } return false; }; function expand(a, recursive) { var s = a.nextSibling || {}, oldClass = s.className; if (/\bsf-dump-compact\b/.test(oldClass)) { toggle(a, recursive); return true; } return false; }; function collapseAll(root) { var a = root.querySelector('a.sf-dump-toggle'); if (a) { collapse(a, true); expand(a); return true; } return false; } function reveal(node) { var previous, parents = []; while ((node = node.parentNode || {}) && (previous = node.previousSibling) && 'A' === previous.tagName) { parents.push(previous); } if (0 !== parents.length) { parents.forEach(function (parent) { expand(parent); }); return true; } return false; } function highlight(root, activeNode, nodes) { resetHighlightedNodes(root); Array.from(nodes||[]).forEach(function (node) { if (!/\bsf-dump-highlight\b/.test(node.className)) { node.className = node.className + ' sf-dump-highlight'; } }); if (!/\bsf-dump-highlight-active\b/.test(activeNode.className)) { activeNode.className = activeNode.className + ' sf-dump-highlight-active'; } } function resetHighlightedNodes(root) { Array.from(root.querySelectorAll('.sf-dump-str, .sf-dump-key, .sf-dump-public, .sf-dump-protected, .sf-dump-private')).forEach(function (strNode) { strNode.className = strNode.className.replace(/\bsf-dump-highlight\b/, ''); strNode.className = strNode.className.replace(/\bsf-dump-highlight-active\b/, ''); }); } return function (root, x) { root = doc.getElementById(root); var indentRx = new RegExp('^('+(root.getAttribute('data-indent-pad') || ' ').replace(rxEsc, '\\$1')+')+', 'm'), options = {"maxDepth":1,"maxStringLength":160,"fileLinkFormat":false}, elt = root.getElementsByTagName('A'), len = elt.length, i = 0, s, h, t = []; while (i < len) t.push(elt[i++]); for (i in x) { options[i] = x[i]; } function a(e, f) { addEventListener(root, e, function (e, n) { if ('A' == e.target.tagName) { f(e.target, e); } else if ('A' == e.target.parentNode.tagName) { f(e.target.parentNode, e); } else { n = /\bsf-dump-ellipsis\b/.test(e.target.className) ? e.target.parentNode : e.target; if ((n = n.nextElementSibling) && 'A' == n.tagName) { if (!/\bsf-dump-toggle\b/.test(n.className)) { n = n.nextElementSibling || n; } f(n, e, true); } } }); }; function isCtrlKey(e) { return e.ctrlKey || e.metaKey; } function xpathString(str) { var parts = str.match(/[^'"]+|['"]/g).map(function (part) { if ("'" == part) { return '"\'"'; } if ('"' == part) { return "'\"'"; } return "'" + part + "'"; }); return "concat(" + parts.join(",") + ", '')"; } function xpathHasClass(className) { return "contains(concat(' ', normalize-space(@class), ' '), ' " + className +" ')"; } a('mouseover', function (a, e, c) { if (c) { e.target.style.cursor = "pointer"; } }); a('click', function (a, e, c) { if (/\bsf-dump-toggle\b/.test(a.className)) { e.preventDefault(); if (!toggle(a, isCtrlKey(e))) { var r = doc.getElementById(a.getAttribute('href').slice(1)), s = r.previousSibling, f = r.parentNode, t = a.parentNode; t.replaceChild(r, a); f.replaceChild(a, s); t.insertBefore(s, r); f = f.firstChild.nodeValue.match(indentRx); t = t.firstChild.nodeValue.match(indentRx); if (f && t && f[0] !== t[0]) { r.innerHTML = r.innerHTML.replace(new RegExp('^'+f[0].replace(rxEsc, '\\$1'), 'mg'), t[0]); } if (/\bsf-dump-compact\b/.test(r.className)) { toggle(s, isCtrlKey(e)); } } if (c) { } else if (doc.getSelection) { try { doc.getSelection().removeAllRanges(); } catch (e) { doc.getSelection().empty(); } } else { doc.selection.empty(); } } else if (/\bsf-dump-str-toggle\b/.test(a.className)) { e.preventDefault(); e = a.parentNode.parentNode; e.className = e.className.replace(/\bsf-dump-str-(expand|collapse)\b/, a.parentNode.className); } }); elt = root.getElementsByTagName('SAMP'); len = elt.length; i = 0; while (i < len) t.push(elt[i++]); len = t.length; for (i = 0; i < len; ++i) { elt = t[i]; if ('SAMP' == elt.tagName) { a = elt.previousSibling || {}; if ('A' != a.tagName) { a = doc.createElement('A'); a.className = 'sf-dump-ref'; elt.parentNode.insertBefore(a, elt); } else { a.innerHTML += ' '; } a.title = (a.title ? a.title+'\n[' : '[')+keyHint+'+click] Expand all children'; a.innerHTML += elt.className == 'sf-dump-compact' ? '<span>&#9654;</span>' : '<span>&#9660;</span>'; a.className += ' sf-dump-toggle'; x = 1; if ('sf-dump' != elt.parentNode.className) { x += elt.parentNode.getAttribute('data-depth')/1; } } else if (/\bsf-dump-ref\b/.test(elt.className) && (a = elt.getAttribute('href'))) { a = a.slice(1); elt.className += ' sf-dump-hover'; elt.className += ' '+a; if (/[\[{]$/.test(elt.previousSibling.nodeValue)) { a = a != elt.nextSibling.id && doc.getElementById(a); try { s = a.nextSibling; elt.appendChild(a); s.parentNode.insertBefore(a, s); if (/^[@#]/.test(elt.innerHTML)) { elt.innerHTML += ' <span>&#9654;</span>'; } else { elt.innerHTML = '<span>&#9654;</span>'; elt.className = 'sf-dump-ref'; } elt.className += ' sf-dump-toggle'; } catch (e) { if ('&' == elt.innerHTML.charAt(0)) { elt.innerHTML = '&#8230;'; elt.className = 'sf-dump-ref'; } } } } } if (doc.evaluate && Array.from && root.children.length > 1) { root.setAttribute('tabindex', 0); SearchState = function () { this.nodes = []; this.idx = 0; }; SearchState.prototype = { next: function () { if (this.isEmpty()) { return this.current(); } this.idx = this.idx < (this.nodes.length - 1) ? this.idx + 1 : 0; return this.current(); }, previous: function () { if (this.isEmpty()) { return this.current(); } this.idx = this.idx > 0 ? this.idx - 1 : (this.nodes.length - 1); return this.current(); }, isEmpty: function () { return 0 === this.count(); }, current: function () { if (this.isEmpty()) { return null; } return this.nodes[this.idx]; }, reset: function () { this.nodes = []; this.idx = 0; }, count: function () { return this.nodes.length; }, }; function showCurrent(state) { var currentNode = state.current(), currentRect, searchRect; if (currentNode) { reveal(currentNode); highlight(root, currentNode, state.nodes); if ('scrollIntoView' in currentNode) { currentNode.scrollIntoView(true); currentRect = currentNode.getBoundingClientRect(); searchRect = search.getBoundingClientRect(); if (currentRect.top < (searchRect.top + searchRect.height)) { window.scrollBy(0, -(searchRect.top + searchRect.height + 5)); } } } counter.textContent = (state.isEmpty() ? 0 : state.idx + 1) + ' of ' + state.count(); } var search = doc.createElement('div'); search.className = 'sf-dump-search-wrapper sf-dump-search-hidden'; search.innerHTML = ' <input type="text" class="sf-dump-search-input"> <span class="sf-dump-search-count">0 of 0<\/span> <button type="button" class="sf-dump-search-input-previous" tabindex="-1"> <svg viewBox="0 0 1792 1792" xmlns="http://www.w3.org/2000/svg"><path d="M1683 1331l-166 165q-19 19-45 19t-45-19L896 965l-531 531q-19 19-45 19t-45-19l-166-165q-19-19-19-45.5t19-45.5l742-741q19-19 45-19t45 19l742 741q19 19 19 45.5t-19 45.5z"\/><\/svg> <\/button> <button type="button" class="sf-dump-search-input-next" tabindex="-1"> <svg viewBox="0 0 1792 1792" xmlns="http://www.w3.org/2000/svg"><path d="M1683 808l-742 741q-19 19-45 19t-45-19L109 808q-19-19-19-45.5t19-45.5l166-165q19-19 45-19t45 19l531 531 531-531q19-19 45-19t45 19l166 165q19 19 19 45.5t-19 45.5z"\/><\/svg> <\/button> '; root.insertBefore(search, root.firstChild); var state = new SearchState(); var searchInput = search.querySelector('.sf-dump-search-input'); var counter = search.querySelector('.sf-dump-search-count'); var searchInputTimer = 0; var previousSearchQuery = ''; addEventListener(searchInput, 'keyup', function (e) { var searchQuery = e.target.value; /* Don't perform anything if the pressed key didn't change the query */ if (searchQuery === previousSearchQuery) { return; } previousSearchQuery = searchQuery; clearTimeout(searchInputTimer); searchInputTimer = setTimeout(function () { state.reset(); collapseAll(root); resetHighlightedNodes(root); if ('' === searchQuery) { counter.textContent = '0 of 0'; return; } var classMatches = [ "sf-dump-str", "sf-dump-key", "sf-dump-public", "sf-dump-protected", "sf-dump-private", ].map(xpathHasClass).join(' or '); var xpathResult = doc.evaluate('.//span[' + classMatches + '][contains(translate(child::text(), ' + xpathString(searchQuery.toUpperCase()) + ', ' + xpathString(searchQuery.toLowerCase()) + '), ' + xpathString(searchQuery.toLowerCase()) + ')]', root, null, XPathResult.ORDERED_NODE_ITERATOR_TYPE, null); while (node = xpathResult.iterateNext()) state.nodes.push(node); showCurrent(state); }, 400); }); Array.from(search.querySelectorAll('.sf-dump-search-input-next, .sf-dump-search-input-previous')).forEach(function (btn) { addEventListener(btn, 'click', function (e) { e.preventDefault(); -1 !== e.target.className.indexOf('next') ? state.next() : state.previous(); searchInput.focus(); collapseAll(root); showCurrent(state); }) }); addEventListener(root, 'keydown', function (e) { var isSearchActive = !/\bsf-dump-search-hidden\b/.test(search.className); if ((114 === e.keyCode && !isSearchActive) || (isCtrlKey(e) && 70 === e.keyCode)) { /* F3 or CMD/CTRL + F */ if (70 === e.keyCode && document.activeElement === searchInput) { /* * If CMD/CTRL + F is hit while having focus on search input, * the user probably meant to trigger browser search instead. * Let the browser execute its behavior: */ return; } e.preventDefault(); search.className = search.className.replace(/\bsf-dump-search-hidden\b/, ''); searchInput.focus(); } else if (isSearchActive) { if (27 === e.keyCode) { /* ESC key */ search.className += ' sf-dump-search-hidden'; e.preventDefault(); resetHighlightedNodes(root); searchInput.value = ''; } else if ( (isCtrlKey(e) && 71 === e.keyCode) /* CMD/CTRL + G */ || 13 === e.keyCode /* Enter */ || 114 === e.keyCode /* F3 */ ) { e.preventDefault(); e.shiftKey ? state.previous() : state.next(); collapseAll(root); showCurrent(state); } } }); } if (0 >= options.maxStringLength) { return; } try { elt = root.querySelectorAll('.sf-dump-str'); len = elt.length; i = 0; t = []; while (i < len) t.push(elt[i++]); len = t.length; for (i = 0; i < len; ++i) { elt = t[i]; s = elt.innerText || elt.textContent; x = s.length - options.maxStringLength; if (0 < x) { h = elt.innerHTML; elt[elt.innerText ? 'innerText' : 'textContent'] = s.substring(0, options.maxStringLength); elt.className += ' sf-dump-str-collapse'; elt.innerHTML = '<span class=sf-dump-str-collapse>'+h+'<a class="sf-dump-ref sf-dump-str-toggle" title="Collapse"> &#9664;</a></span>'+ '<span class=sf-dump-str-expand>'+elt.innerHTML+'<a class="sf-dump-ref sf-dump-str-toggle" title="'+x+' remaining characters"> &#9654;</a></span>'; } } } catch (e) { } }; })(document); </script><style> .sf-js-enabled pre.sf-dump .sf-dump-compact, .sf-js-enabled .sf-dump-str-collapse .sf-dump-str-collapse, .sf-js-enabled .sf-dump-str-expand .sf-dump-str-expand { display: none; } .sf-dump-hover:hover { background-color: #B729D9; color: #FFF !important; border-radius: 2px; } pre.sf-dump { display: block; white-space: pre; padding: 5px; overflow: initial !important; } pre.sf-dump:after { content: ""; visibility: hidden; display: block; height: 0; clear: both; } pre.sf-dump .sf-dump-ellipsization { display: inline-flex; } pre.sf-dump a { text-decoration: none; cursor: pointer; border: 0; outline: none; color: inherit; } pre.sf-dump img { max-width: 50em; max-height: 50em; margin: .5em 0 0 0; padding: 0; background: url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAAAAAA6mKC9AAAAHUlEQVQY02O8zAABilCaiQEN0EeA8QuUcX9g3QEAAjcC5piyhyEAAAAASUVORK5CYII=) #D3D3D3; } pre.sf-dump .sf-dump-ellipsis { text-overflow: ellipsis; white-space: nowrap; overflow: hidden; } pre.sf-dump .sf-dump-ellipsis-tail { flex-shrink: 0; } pre.sf-dump code { display:inline; padding:0; background:none; } .sf-dump-public.sf-dump-highlight, .sf-dump-protected.sf-dump-highlight, .sf-dump-private.sf-dump-highlight, .sf-dump-str.sf-dump-highlight, .sf-dump-key.sf-dump-highlight { background: rgba(111, 172, 204, 0.3); border: 1px solid #7DA0B1; border-radius: 3px; } .sf-dump-public.sf-dump-highlight-active, .sf-dump-protected.sf-dump-highlight-active, .sf-dump-private.sf-dump-highlight-active, .sf-dump-str.sf-dump-highlight-active, .sf-dump-key.sf-dump-highlight-active { background: rgba(253, 175, 0, 0.4); border: 1px solid #ffa500; border-radius: 3px; } pre.sf-dump .sf-dump-search-hidden { display: none !important; } pre.sf-dump .sf-dump-search-wrapper { font-size: 0; white-space: nowrap; margin-bottom: 5px; display: flex; position: -webkit-sticky; position: sticky; top: 5px; } pre.sf-dump .sf-dump-search-wrapper > * { vertical-align: top; box-sizing: border-box; height: 21px; font-weight: normal; border-radius: 0; background: #FFF; color: #757575; border: 1px solid #BBB; } pre.sf-dump .sf-dump-search-wrapper > input.sf-dump-search-input { padding: 3px; height: 21px; font-size: 12px; border-right: none; border-top-left-radius: 3px; border-bottom-left-radius: 3px; color: #000; min-width: 15px; width: 100%; } pre.sf-dump .sf-dump-search-wrapper > .sf-dump-search-input-next, pre.sf-dump .sf-dump-search-wrapper > .sf-dump-search-input-previous { background: #F2F2F2; outline: none; border-left: none; font-size: 0; line-height: 0; } pre.sf-dump .sf-dump-search-wrapper > .sf-dump-search-input-next { border-top-right-radius: 3px; border-bottom-right-radius: 3px; } pre.sf-dump .sf-dump-search-wrapper > .sf-dump-search-input-next > svg, pre.sf-dump .sf-dump-search-wrapper > .sf-dump-search-input-previous > svg { pointer-events: none; width: 12px; height: 12px; } pre.sf-dump .sf-dump-search-wrapper > .sf-dump-search-count { display: inline-block; padding: 0 5px; margin: 0; border-left: none; line-height: 21px; font-size: 12px; }pre.sf-dump, pre.sf-dump .sf-dump-default{background-color:#18171B; color:#FF8400; line-height:1.2em; font:12px Menlo, Monaco, Consolas, monospace; word-wrap: break-word; white-space: pre-wrap; position:relative; z-index:99999; word-break: break-all}pre.sf-dump .sf-dump-num{font-weight:bold; color:#1299DA}pre.sf-dump .sf-dump-const{font-weight:bold}pre.sf-dump .sf-dump-virtual{font-style:italic}pre.sf-dump .sf-dump-str{font-weight:bold; color:#56DB3A}pre.sf-dump .sf-dump-note{color:#1299DA}pre.sf-dump .sf-dump-ref{color:#A0A0A0}pre.sf-dump .sf-dump-public{color:#FFFFFF}pre.sf-dump .sf-dump-protected{color:#FFFFFF}pre.sf-dump .sf-dump-private{color:#FFFFFF}pre.sf-dump .sf-dump-meta{color:#B729D9}pre.sf-dump .sf-dump-key{color:#56DB3A}pre.sf-dump .sf-dump-index{color:#1299DA}pre.sf-dump .sf-dump-ellipsis{color:#FF8400}pre.sf-dump .sf-dump-ns{user-select:none;}pre.sf-dump .sf-dump-ellipsis-note{color:#1299DA}</style><pre class=sf-dump id=sf-dump-175875379 data-indent-pad="  "><span class=sf-dump-note>Illuminate\Http\Request</span> {<a class=sf-dump-ref>#45</a><samp data-depth=1 class=sf-dump-expanded><span style="color: #A0A0A0;"> // app/Http/Controllers/Demo/DumpAndDieController.php:140</span>
  +<span class=sf-dump-public title="Public property">attributes</span>: <span class="sf-dump-note sf-dump-ellipsization" title="Symfony\Component\HttpFoundation\ParameterBag
"><span class="sf-dump-ellipsis sf-dump-ellipsis-note">Symfony\Component\HttpFoundation</span><span class="sf-dump-ellipsis sf-dump-ellipsis-note">\</span><span class="sf-dump-ellipsis-tail">ParameterBag</span></span> {<a class=sf-dump-ref>#50</a><samp data-depth=2 class=sf-dump-compact>
    #<span class=sf-dump-protected title="Protected property">parameters</span>: []
  </samp>}
  +<span class=sf-dump-public title="Public property">request</span>: <span class="sf-dump-note sf-dump-ellipsization" title="Symfony\Component\HttpFoundation\InputBag
"><span class="sf-dump-ellipsis sf-dump-ellipsis-note">Symfony\Component\HttpFoundation</span><span class="sf-dump-ellipsis sf-dump-ellipsis-note">\</span><span class="sf-dump-ellipsis-tail">InputBag</span></span> {<a class=sf-dump-ref href=#sf-dump-175875379-ref249 title="2 occurrences">#49</a><samp data-depth=2 id=sf-dump-175875379-ref249 class=sf-dump-compact>
    #<span class=sf-dump-protected title="Protected property">parameters</span>: []
  </samp>}
  +<span class=sf-dump-public title="Public property">query</span>: <span class="sf-dump-note sf-dump-ellipsization" title="Symfony\Component\HttpFoundation\InputBag
"><span class="sf-dump-ellipsis sf-dump-ellipsis-note">Symfony\Component\HttpFoundation</span><span class="sf-dump-ellipsis sf-dump-ellipsis-note">\</span><span class="sf-dump-ellipsis-tail">InputBag</span></span> {<a class=sf-dump-ref>#53</a><samp data-depth=2 class=sf-dump-compact>
    #<span class=sf-dump-protected title="Protected property">parameters</span>: []
  </samp>}
  +<span class=sf-dump-public title="Public property">server</span>: <span class="sf-dump-note sf-dump-ellipsization" title="Symfony\Component\HttpFoundation\ServerBag
"><span class="sf-dump-ellipsis sf-dump-ellipsis-note">Symfony\Component\HttpFoundation</span><span class="sf-dump-ellipsis sf-dump-ellipsis-note">\</span><span class="sf-dump-ellipsis-tail">ServerBag</span></span> {<a class=sf-dump-ref>#48</a><samp data-depth=2 class=sf-dump-compact>
    #<span class=sf-dump-protected title="Protected property">parameters</span>: <span class=sf-dump-note>array:24</span> [<samp data-depth=3 class=sf-dump-compact>
      "<span class=sf-dump-key>DOCUMENT_ROOT</span>" => "<span class=sf-dump-str title="30 characters">/Volumes/Dev/nimbus-dev/public</span>"
      "<span class=sf-dump-key>REMOTE_ADDR</span>" => "<span class=sf-dump-str title="9 characters">127.0.0.1</span>"
      "<span class=sf-dump-key>REMOTE_PORT</span>" => "<span class=sf-dump-str title="5 characters">49835</span>"
      "<span class=sf-dump-key>SERVER_SOFTWARE</span>" => "<span class=sf-dump-str title="31 characters">PHP/8.3.26 (Development Server)</span>"
      "<span class=sf-dump-key>SERVER_PROTOCOL</span>" => "<span class=sf-dump-str title="8 characters">HTTP/1.1</span>"
      "<span class=sf-dump-key>SERVER_NAME</span>" => "<span class=sf-dump-str title="9 characters">127.0.0.1</span>"
      "<span class=sf-dump-key>SERVER_PORT</span>" => "<span class=sf-dump-str title="4 characters">8001</span>"
      "<span class=sf-dump-key>REQUEST_URI</span>" => "<span class=sf-dump-str title="9 characters">/_demo/dd</span>"
      "<span class=sf-dump-key>REQUEST_METHOD</span>" => "<span class=sf-dump-str title="3 characters">GET</span>"
      "<span class=sf-dump-key>SCRIPT_NAME</span>" => "<span class=sf-dump-str title="10 characters">/index.php</span>"
      "<span class=sf-dump-key>SCRIPT_FILENAME</span>" => "<span class=sf-dump-str title="40 characters">/Volumes/Dev/nimbus-dev/public/index.php</span>"
      "<span class=sf-dump-key>PATH_INFO</span>" => "<span class=sf-dump-str title="9 characters">/_demo/dd</span>"
      "<span class=sf-dump-key>PHP_SELF</span>" => "<span class=sf-dump-str title="19 characters">/index.php/_demo/dd</span>"
      "<span class=sf-dump-key>HTTP_HOST</span>" => "<span class=sf-dump-str title="14 characters">127.0.0.1:8001</span>"
      "<span class=sf-dump-key>HTTP_CLI</span>" => "<span class=sf-dump-str title="3 characters">cli</span>"
      "<span class=sf-dump-key>CONTENT_TYPE</span>" => "<span class=sf-dump-str title="16 characters">application/json</span>"
      "<span class=sf-dump-key>HTTP_CONTENT_TYPE</span>" => "<span class=sf-dump-str title="16 characters">application/json</span>"
      "<span class=sf-dump-key>HTTP_X_REQUEST_ID</span>" => "<span class=sf-dump-str title="36 characters">920bdbda-0bef-45c5-bc71-be6b13491573</span>"
      "<span class=sf-dump-key>HTTP_X_SESSION_ID</span>" => "<span class=sf-dump-str title="36 characters">ca955ea8-5cab-405d-83a2-46ce24777d72</span>"
      "<span class=sf-dump-key>HTTP_ACCEPT</span>" => "<span class=sf-dump-str title="3 characters">*/*</span>"
      "<span class=sf-dump-key>CONTENT_LENGTH</span>" => "<span class=sf-dump-str>0</span>"
      "<span class=sf-dump-key>HTTP_CONTENT_LENGTH</span>" => "<span class=sf-dump-str>0</span>"
      "<span class=sf-dump-key>REQUEST_TIME_FLOAT</span>" => <span class=sf-dump-num>1767295856.229</span>
      "<span class=sf-dump-key>REQUEST_TIME</span>" => <span class=sf-dump-num>1767295856</span>
    </samp>]
  </samp>}
  +<span class=sf-dump-public title="Public property">files</span>: <span class="sf-dump-note sf-dump-ellipsization" title="Symfony\Component\HttpFoundation\FileBag
"><span class="sf-dump-ellipsis sf-dump-ellipsis-note">Symfony\Component\HttpFoundation</span><span class="sf-dump-ellipsis sf-dump-ellipsis-note">\</span><span class="sf-dump-ellipsis-tail">FileBag</span></span> {<a class=sf-dump-ref>#52</a><samp data-depth=2 class=sf-dump-compact>
    #<span class=sf-dump-protected title="Protected property">parameters</span>: []
  </samp>}
  +<span class=sf-dump-public title="Public property">cookies</span>: <span class="sf-dump-note sf-dump-ellipsization" title="Symfony\Component\HttpFoundation\InputBag
"><span class="sf-dump-ellipsis sf-dump-ellipsis-note">Symfony\Component\HttpFoundation</span><span class="sf-dump-ellipsis sf-dump-ellipsis-note">\</span><span class="sf-dump-ellipsis-tail">InputBag</span></span> {<a class=sf-dump-ref>#51</a><samp data-depth=2 class=sf-dump-compact>
    #<span class=sf-dump-protected title="Protected property">parameters</span>: []
  </samp>}
  +<span class=sf-dump-public title="Public property">headers</span>: <span class="sf-dump-note sf-dump-ellipsization" title="Symfony\Component\HttpFoundation\HeaderBag
"><span class="sf-dump-ellipsis sf-dump-ellipsis-note">Symfony\Component\HttpFoundation</span><span class="sf-dump-ellipsis sf-dump-ellipsis-note">\</span><span class="sf-dump-ellipsis-tail">HeaderBag</span></span> {<a class=sf-dump-ref>#47</a><samp data-depth=2 class=sf-dump-compact>
    #<span class=sf-dump-protected title="Protected property">headers</span>: <span class=sf-dump-note>array:7</span> [<samp data-depth=3 class=sf-dump-compact>
      "<span class=sf-dump-key>host</span>" => <span class=sf-dump-note>array:1</span> [<samp data-depth=4 class=sf-dump-compact>
        <span class=sf-dump-index>0</span> => "<span class=sf-dump-str title="14 characters">127.0.0.1:8001</span>"
      </samp>]
      "<span class=sf-dump-key>cli</span>" => <span class=sf-dump-note>array:1</span> [<samp data-depth=4 class=sf-dump-compact>
        <span class=sf-dump-index>0</span> => "<span class=sf-dump-str title="3 characters">cli</span>"
      </samp>]
      "<span class=sf-dump-key>content-type</span>" => <span class=sf-dump-note>array:1</span> [<samp data-depth=4 class=sf-dump-compact>
        <span class=sf-dump-index>0</span> => "<span class=sf-dump-str title="16 characters">application/json</span>"
      </samp>]
      "<span class=sf-dump-key>x-request-id</span>" => <span class=sf-dump-note>array:1</span> [<samp data-depth=4 class=sf-dump-compact>
        <span class=sf-dump-index>0</span> => "<span class=sf-dump-str title="36 characters">920bdbda-0bef-45c5-bc71-be6b13491573</span>"
      </samp>]
      "<span class=sf-dump-key>x-session-id</span>" => <span class=sf-dump-note>array:1</span> [<samp data-depth=4 class=sf-dump-compact>
        <span class=sf-dump-index>0</span> => "<span class=sf-dump-str title="36 characters">ca955ea8-5cab-405d-83a2-46ce24777d72</span>"
      </samp>]
      "<span class=sf-dump-key>accept</span>" => <span class=sf-dump-note>array:1</span> [<samp data-depth=4 class=sf-dump-compact>
        <span class=sf-dump-index>0</span> => "<span class=sf-dump-str title="3 characters">*/*</span>"
      </samp>]
      "<span class=sf-dump-key>content-length</span>" => <span class=sf-dump-note>array:1</span> [<samp data-depth=4 class=sf-dump-compact>
        <span class=sf-dump-index>0</span> => "<span class=sf-dump-str>0</span>"
      </samp>]
    </samp>]
    #<span class=sf-dump-protected title="Protected property">cacheControl</span>: []
  </samp>}
  #<span class=sf-dump-protected title="Protected property">content</span>: ""
  #<span class=sf-dump-protected title="Protected property">languages</span>: <span class=sf-dump-const>null</span>
  #<span class=sf-dump-protected title="Protected property">charsets</span>: <span class=sf-dump-const>null</span>
  #<span class=sf-dump-protected title="Protected property">encodings</span>: <span class=sf-dump-const>null</span>
  #<span class=sf-dump-protected title="Protected property">acceptableContentTypes</span>: <span class=sf-dump-const>null</span>
  #<span class=sf-dump-protected title="Protected property">pathInfo</span>: "<span class=sf-dump-str title="9 characters">/_demo/dd</span>"
  #<span class=sf-dump-protected title="Protected property">requestUri</span>: "<span class=sf-dump-str title="9 characters">/_demo/dd</span>"
  #<span class=sf-dump-protected title="Protected property">baseUrl</span>: ""
  #<span class=sf-dump-protected title="Protected property">basePath</span>: <span class=sf-dump-const>null</span>
  #<span class=sf-dump-protected title="Protected property">method</span>: "<span class=sf-dump-str title="3 characters">GET</span>"
  #<span class=sf-dump-protected title="Protected property">format</span>: <span class=sf-dump-const>null</span>
  #<span class=sf-dump-protected title="Protected property">session</span>: <span class=sf-dump-const>null</span>
  #<span class=sf-dump-protected title="Protected property">locale</span>: <span class=sf-dump-const>null</span>
  #<span class=sf-dump-protected title="Protected property">locale</span>: <span class=sf-dump-const>null</span>
  #<span class=sf-dump-protected title="Protected property">defaultLocale</span>: "<span class=sf-dump-str title="2 characters">en</span>"
  -<span class=sf-dump-private title="Private property defined in class:&#10;`Symfony\Component\HttpFoundation\Request`">preferredFormat</span>: <span class=sf-dump-const>null</span>
  -<span class=sf-dump-private title="Private property defined in class:&#10;`Symfony\Component\HttpFoundation\Request`">isHostValid</span>: <span class=sf-dump-const>true</span>
  -<span class=sf-dump-private title="Private property defined in class:&#10;`Symfony\Component\HttpFoundation\Request`">isForwardedValid</span>: <span class=sf-dump-const>true</span>
  -<span class=sf-dump-private title="Private property defined in class:&#10;`Symfony\Component\HttpFoundation\Request`">isSafeContentPreferred</span>: <span class=sf-dump-const title="Uninitialized property">? bool</span>
  -<span class=sf-dump-private title="Private property defined in class:&#10;`Symfony\Component\HttpFoundation\Request`">undefinedUninitialized</span>: <span class=sf-dump-const title="Uninitialized property"></span>
  -<span class=sf-dump-private title="Private property defined in class:&#10;`Symfony\Component\HttpFoundation\Request`">trustedValuesCache</span>: []
  -<span class=sf-dump-private title="Private property defined in class:&#10;`Symfony\Component\HttpFoundation\Request`">isIisRewrite</span>: <span class=sf-dump-const>false</span>
  #<span class=sf-dump-protected title="Protected property">json</span>: <span class="sf-dump-note sf-dump-ellipsization" title="Symfony\Component\HttpFoundation\InputBag
"><span class="sf-dump-ellipsis sf-dump-ellipsis-note">Symfony\Component\HttpFoundation</span><span class="sf-dump-ellipsis sf-dump-ellipsis-note">\</span><span class="sf-dump-ellipsis-tail">InputBag</span></span> {<a class=sf-dump-ref href=#sf-dump-175875379-ref249 title="2 occurrences">#49</a>}
  #<span class=sf-dump-protected title="Protected property">convertedFiles</span>: []
  #<span class=sf-dump-protected title="Protected property">userResolver</span>: <span class=sf-dump-note>Closure($guard = null)</span> {<a class=sf-dump-ref>#350</a><samp data-depth=2 class=sf-dump-compact>
    <span class=sf-dump-meta>class</span>: "<span class="sf-dump-str sf-dump-ellipsization" title="Illuminate\Auth\AuthServiceProvider
35 characters"><span class="sf-dump-ellipsis sf-dump-ellipsis-class">Illuminate\Auth</span><span class="sf-dump-ellipsis sf-dump-ellipsis-class">\</span><span class="sf-dump-ellipsis-tail">AuthServiceProvider</span></span>"
    <span class=sf-dump-meta>this</span>: <span class="sf-dump-note sf-dump-ellipsization" title="Illuminate\Auth\AuthServiceProvider
"><span class="sf-dump-ellipsis sf-dump-ellipsis-note">Illuminate\Auth</span><span class="sf-dump-ellipsis sf-dump-ellipsis-note">\</span><span class="sf-dump-ellipsis-tail">AuthServiceProvider</span></span> {<a class=sf-dump-ref>#67</a> &#8230;}
    <span class=sf-dump-meta>use</span>: {<samp data-depth=3 class=sf-dump-compact>
      <span class=sf-dump-meta>$app</span>: <span class="sf-dump-note sf-dump-ellipsization" title="Illuminate\Foundation\Application
"><span class="sf-dump-ellipsis sf-dump-ellipsis-note">Illuminate\Foundation</span><span class="sf-dump-ellipsis sf-dump-ellipsis-note">\</span><span class="sf-dump-ellipsis-tail">Application</span></span> {<a class=sf-dump-ref>#4</a> &#8230;}
    </samp>}
  </samp>}
  #<span class=sf-dump-protected title="Protected property">routeResolver</span>: <span class=sf-dump-note>Closure()</span> {<a class=sf-dump-ref>#360</a><samp data-depth=2 class=sf-dump-compact>
    <span class=sf-dump-meta>class</span>: "<span class="sf-dump-str sf-dump-ellipsization" title="Illuminate\Routing\Router
25 characters"><span class="sf-dump-ellipsis sf-dump-ellipsis-class">Illuminate\Routing</span><span class="sf-dump-ellipsis sf-dump-ellipsis-class">\</span><span class="sf-dump-ellipsis-tail">Router</span></span>"
    <span class=sf-dump-meta>this</span>: <span class="sf-dump-note sf-dump-ellipsization" title="Illuminate\Routing\Router
"><span class="sf-dump-ellipsis sf-dump-ellipsis-note">Illuminate\Routing</span><span class="sf-dump-ellipsis sf-dump-ellipsis-note">\</span><span class="sf-dump-ellipsis-tail">Router</span></span> {<a class=sf-dump-ref>#43</a> &#8230;}
    <span class=sf-dump-meta>use</span>: {<samp data-depth=3 class=sf-dump-compact>
      <span class=sf-dump-meta>$route</span>: <span class="sf-dump-note sf-dump-ellipsization" title="Illuminate\Routing\Route
"><span class="sf-dump-ellipsis sf-dump-ellipsis-note">Illuminate\Routing</span><span class="sf-dump-ellipsis sf-dump-ellipsis-note">\</span><span class="sf-dump-ellipsis-tail">Route</span></span> {<a class=sf-dump-ref>#342</a> &#8230;}
    </samp>}
  </samp>}
  <span class=sf-dump-meta>basePath</span>: ""
  <span class=sf-dump-meta>format</span>: "<span class=sf-dump-str title="4 characters">html</span>"
</samp>}
</pre><script>Sfdump("sf-dump-175875379")</script>
HTML,
            'expectedClass' => 'Illuminate\Http\Request',
            'expectedProperties' => [
                'attributes' => [
                    'visibility' => 'public',
                    'value' => [
                        'type' => DumpValueTypeEnum::Object->value,
                        'value' => [
                            'class' => 'Symfony\\Component\\HttpFoundation\\ParameterBag',
                            'properties' => [
                                'parameters' => [
                                    'visibility' => 'protected',
                                    'value' => [
                                        'type' => DumpValueTypeEnum::Array->value,
                                        'value' => [
                                            'items' => [],
                                            'length' => 0,
                                            'numericallyIndexed' => true,
                                        ],
                                    ],
                                ],
                            ],
                            'propertiesCount' => 1,
                        ],
                    ],
                ],
                'request' => [
                    'visibility' => 'public',
                    'value' => [
                        'type' => DumpValueTypeEnum::Object->value,
                        'value' => [
                            'class' => 'Symfony\\Component\\HttpFoundation\\InputBag',
                            'properties' => [
                                'parameters' => [
                                    'value' => [
                                        'type' => DumpValueTypeEnum::Array->value,
                                        'value' => [
                                            'items' => [],
                                            'length' => 0,
                                            'numericallyIndexed' => true,
                                        ],
                                    ],
                                    'visibility' => 'protected',
                                ],
                            ],
                            'propertiesCount' => 1,
                        ],
                    ],
                ],
                'query' => [
                    'visibility' => 'public',
                    'value' => [
                        'type' => DumpValueTypeEnum::Object->value,
                        'value' => [
                            'class' => 'Symfony\\Component\\HttpFoundation\\InputBag',
                            'properties' => [
                                'parameters' => [
                                    'visibility' => 'protected',
                                    'value' => [
                                        'type' => DumpValueTypeEnum::Array->value,
                                        'value' => [
                                            'items' => [],
                                            'length' => 0,
                                            'numericallyIndexed' => true,
                                        ],
                                    ],
                                ],
                            ],
                            'propertiesCount' => 1,
                        ],
                    ],
                ],
                'server' => [
                    'visibility' => 'public',
                    'value' => [
                        'type' => DumpValueTypeEnum::Object->value,
                        'value' => [
                            'class' => 'Symfony\\Component\\HttpFoundation\\ServerBag',
                            'properties' => [
                                'parameters' => [
                                    'visibility' => 'protected',
                                    'value' => [
                                        'type' => DumpValueTypeEnum::Array->value,
                                        'value' => [
                                            'items' => [
                                                'DOCUMENT_ROOT' => ['type' => DumpValueTypeEnum::String->value, 'value' => '/Volumes/Dev/nimbus-dev/public'],
                                                'REMOTE_ADDR' => ['type' => DumpValueTypeEnum::String->value, 'value' => '127.0.0.1'],
                                                'REMOTE_PORT' => ['type' => DumpValueTypeEnum::String->value, 'value' => '49835'],
                                                'SERVER_SOFTWARE' => ['type' => DumpValueTypeEnum::String->value, 'value' => 'PHP/8.3.26 (Development Server)'],
                                                'SERVER_PROTOCOL' => ['type' => DumpValueTypeEnum::String->value, 'value' => 'HTTP/1.1'],
                                                'SERVER_NAME' => ['type' => DumpValueTypeEnum::String->value, 'value' => '127.0.0.1'],
                                                'SERVER_PORT' => ['type' => DumpValueTypeEnum::String->value, 'value' => '8001'],
                                                'REQUEST_URI' => ['type' => DumpValueTypeEnum::String->value, 'value' => '/_demo/dd'],
                                                'REQUEST_METHOD' => ['type' => DumpValueTypeEnum::String->value, 'value' => 'GET'],
                                                'SCRIPT_NAME' => ['type' => DumpValueTypeEnum::String->value, 'value' => '/index.php'],
                                                'SCRIPT_FILENAME' => ['type' => DumpValueTypeEnum::String->value, 'value' => '/Volumes/Dev/nimbus-dev/public/index.php'],
                                                'PATH_INFO' => ['type' => DumpValueTypeEnum::String->value, 'value' => '/_demo/dd'],
                                                'PHP_SELF' => ['type' => DumpValueTypeEnum::String->value, 'value' => '/index.php/_demo/dd'],
                                                'HTTP_HOST' => ['type' => DumpValueTypeEnum::String->value, 'value' => '127.0.0.1:8001'],
                                                'HTTP_CLI' => ['type' => DumpValueTypeEnum::String->value, 'value' => 'cli'],
                                                'CONTENT_TYPE' => ['type' => DumpValueTypeEnum::String->value, 'value' => 'application/json'],
                                                'HTTP_CONTENT_TYPE' => ['type' => DumpValueTypeEnum::String->value, 'value' => 'application/json'],
                                                'HTTP_X_REQUEST_ID' => ['type' => DumpValueTypeEnum::String->value, 'value' => '920bdbda-0bef-45c5-bc71-be6b13491573'],
                                                'HTTP_X_SESSION_ID' => ['type' => DumpValueTypeEnum::String->value, 'value' => 'ca955ea8-5cab-405d-83a2-46ce24777d72'],
                                                'HTTP_ACCEPT' => ['type' => DumpValueTypeEnum::String->value, 'value' => '*/*'],
                                                'CONTENT_LENGTH' => ['type' => DumpValueTypeEnum::String->value, 'value' => '0'],
                                                'HTTP_CONTENT_LENGTH' => ['type' => DumpValueTypeEnum::String->value, 'value' => '0'],
                                                'REQUEST_TIME_FLOAT' => ['type' => DumpValueTypeEnum::Number->value, 'value' => 1767295856.229],
                                                'REQUEST_TIME' => ['type' => DumpValueTypeEnum::Number->value, 'value' => 1767295856],
                                            ],
                                            'length' => 24,
                                            'numericallyIndexed' => false,
                                        ],
                                    ],
                                ],
                            ],
                            'propertiesCount' => 1,
                        ],
                    ],
                ],
                'files' => [
                    'visibility' => 'public',
                    'value' => [
                        'type' => DumpValueTypeEnum::Object->value,
                        'value' => [
                            'class' => 'Symfony\\Component\\HttpFoundation\\FileBag',
                            'properties' => [
                                'parameters' => [
                                    'visibility' => 'protected',
                                    'value' => [
                                        'type' => DumpValueTypeEnum::Array->value,
                                        'value' => [
                                            'items' => [],
                                            'length' => 0,
                                            'numericallyIndexed' => true,
                                        ],
                                    ],
                                ],
                            ],
                            'propertiesCount' => 1,
                        ],
                    ],
                ],
                'cookies' => [
                    'visibility' => 'public',
                    'value' => [
                        'type' => DumpValueTypeEnum::Object->value,
                        'value' => [
                            'class' => 'Symfony\\Component\\HttpFoundation\\InputBag',
                            'properties' => [
                                'parameters' => [
                                    'visibility' => 'protected',
                                    'value' => [
                                        'type' => DumpValueTypeEnum::Array->value,
                                        'value' => [
                                            'items' => [],
                                            'length' => 0,
                                            'numericallyIndexed' => true,
                                        ],
                                    ],
                                ],
                            ],
                            'propertiesCount' => 1,
                        ],
                    ],
                ],
                'headers' => [
                    'visibility' => 'public',
                    'value' => [
                        'type' => DumpValueTypeEnum::Object->value,
                        'value' => [
                            'class' => 'Symfony\\Component\\HttpFoundation\\HeaderBag',
                            'properties' => [
                                'headers' => [
                                    'visibility' => 'protected',
                                    'value' => [
                                        'type' => DumpValueTypeEnum::Array->value,
                                        'value' => [
                                            'items' => [
                                                'host' => [
                                                    'type' => DumpValueTypeEnum::Array->value,
                                                    'value' => [
                                                        'items' => [
                                                            [
                                                                'type' => DumpValueTypeEnum::String->value,
                                                                'value' => '127.0.0.1:8001',
                                                            ],
                                                        ],
                                                        'length' => 1,
                                                        'numericallyIndexed' => true,
                                                    ],
                                                ],
                                                'cli' => [
                                                    'type' => DumpValueTypeEnum::Array->value,
                                                    'value' => [
                                                        'items' => [
                                                            [
                                                                'type' => DumpValueTypeEnum::String->value,
                                                                'value' => 'cli',
                                                            ],
                                                        ],
                                                        'length' => 1,
                                                        'numericallyIndexed' => true,
                                                    ],
                                                ],
                                                'content-type' => [
                                                    'type' => DumpValueTypeEnum::Array->value,
                                                    'value' => [
                                                        'items' => [
                                                            [
                                                                'type' => DumpValueTypeEnum::String->value,
                                                                'value' => 'application/json',
                                                            ],
                                                        ],
                                                        'length' => 1,
                                                        'numericallyIndexed' => true,
                                                    ],
                                                ],
                                                'x-request-id' => [
                                                    'type' => DumpValueTypeEnum::Array->value,
                                                    'value' => [
                                                        'items' => [
                                                            [
                                                                'type' => DumpValueTypeEnum::String->value,
                                                                'value' => '920bdbda-0bef-45c5-bc71-be6b13491573',
                                                            ],
                                                        ],
                                                        'length' => 1,
                                                        'numericallyIndexed' => true,
                                                    ],
                                                ],
                                                'x-session-id' => [
                                                    'type' => DumpValueTypeEnum::Array->value,
                                                    'value' => [
                                                        'items' => [
                                                            [
                                                                'type' => DumpValueTypeEnum::String->value,
                                                                'value' => 'ca955ea8-5cab-405d-83a2-46ce24777d72',
                                                            ],
                                                        ],
                                                        'length' => 1,
                                                        'numericallyIndexed' => true,
                                                    ],
                                                ],
                                                'accept' => [
                                                    'type' => DumpValueTypeEnum::Array->value,
                                                    'value' => [
                                                        'items' => [
                                                            [
                                                                'type' => DumpValueTypeEnum::String->value,
                                                                'value' => '*/*',
                                                            ],
                                                        ],
                                                        'length' => 1,
                                                        'numericallyIndexed' => true,
                                                    ],
                                                ],
                                                'content-length' => [
                                                    'type' => DumpValueTypeEnum::Array->value,
                                                    'value' => [
                                                        'items' => [
                                                            [
                                                                'type' => DumpValueTypeEnum::String->value,
                                                                'value' => '0',
                                                            ],
                                                        ],
                                                        'length' => 1,
                                                        'numericallyIndexed' => true,
                                                    ],
                                                ],
                                            ],
                                            'length' => 7,
                                            'numericallyIndexed' => false,
                                        ],
                                    ],
                                ],
                                'cacheControl' => [
                                    'visibility' => 'protected',
                                    'value' => [
                                        'type' => DumpValueTypeEnum::Array->value,
                                        'value' => [
                                            'items' => [],
                                            'length' => 0,
                                            'numericallyIndexed' => true,
                                        ],
                                    ],
                                ],
                            ],
                            'propertiesCount' => 2,
                        ],
                    ],
                ],
                'content' => [
                    'visibility' => 'protected',
                    'value' => [
                        'type' => DumpValueTypeEnum::String->value,
                        'value' => '',
                    ],
                ],
                'languages' => ['visibility' => 'protected', 'value' => ['type' => DumpValueTypeEnum::Constant->value, 'value' => null]],
                'charsets' => ['visibility' => 'protected', 'value' => ['type' => DumpValueTypeEnum::Constant->value, 'value' => null]],
                'encodings' => ['visibility' => 'protected', 'value' => ['type' => DumpValueTypeEnum::Constant->value, 'value' => null]],
                'acceptableContentTypes' => ['visibility' => 'protected', 'value' => ['type' => DumpValueTypeEnum::Constant->value, 'value' => null]],
                'pathInfo' => ['visibility' => 'protected', 'value' => ['type' => DumpValueTypeEnum::String->value, 'value' => '/_demo/dd']],
                'requestUri' => ['visibility' => 'protected', 'value' => ['type' => DumpValueTypeEnum::String->value, 'value' => '/_demo/dd']],
                'baseUrl' => ['visibility' => 'protected', 'value' => ['type' => DumpValueTypeEnum::String->value, 'value' => '']],
                'basePath' => ['visibility' => 'protected', 'value' => ['type' => DumpValueTypeEnum::Constant->value, 'value' => null]],
                'method' => ['visibility' => 'protected', 'value' => ['type' => DumpValueTypeEnum::String->value, 'value' => 'GET']],
                'format' => ['visibility' => 'protected', 'value' => ['type' => DumpValueTypeEnum::Constant->value, 'value' => null]],
                'session' => ['visibility' => 'protected', 'value' => ['type' => DumpValueTypeEnum::Constant->value, 'value' => null]],
                'locale' => ['visibility' => 'protected', 'value' => ['type' => DumpValueTypeEnum::Constant->value, 'value' => null]],
                'defaultLocale' => ['visibility' => 'protected', 'value' => ['type' => DumpValueTypeEnum::String->value, 'value' => 'en']],
                'preferredFormat' => ['visibility' => 'private', 'value' => ['type' => DumpValueTypeEnum::Constant->value, 'value' => null]],
                'isHostValid' => ['visibility' => 'private', 'value' => ['type' => DumpValueTypeEnum::Constant->value, 'value' => true]],
                'isForwardedValid' => ['visibility' => 'private', 'value' => ['type' => DumpValueTypeEnum::Constant->value, 'value' => true]],
                'isSafeContentPreferred' => ['visibility' => 'private', 'value' => ['type' => DumpValueTypeEnum::Uninitialized->value, 'value' => '? bool']],
                'undefinedUninitialized' => ['visibility' => 'private', 'value' => ['type' => DumpValueTypeEnum::Uninitialized->value, 'value' => 'undefined']],
                'trustedValuesCache' => [
                    'visibility' => 'private',
                    'value' => [
                        'type' => DumpValueTypeEnum::Array->value,
                        'value' => [
                            'items' => [],
                            'length' => 0,
                            'numericallyIndexed' => true,
                        ],
                    ],
                ],
                'isIisRewrite' => ['visibility' => 'private', 'value' => ['type' => DumpValueTypeEnum::Constant->value, 'value' => false]],
                'json' => ['visibility' => 'protected', 'value' => ['type' => DumpValueTypeEnum::Object->value, 'value' => ['class' => 'Symfony\\Component\\HttpFoundation\\InputBag', 'properties' => [], 'propertiesCount' => 0]]],
                'convertedFiles' => [
                    'visibility' => 'protected',
                    'value' => [
                        'type' => DumpValueTypeEnum::Array->value,
                        'value' => [
                            'items' => [],
                            'length' => 0,
                            'numericallyIndexed' => true,
                        ],
                    ],
                ],
                'userResolver' => ['visibility' => 'protected', 'value' => ['type' => DumpValueTypeEnum::Closure->value, 'value' => ['class' => 'Illuminate\\Auth\\AuthServiceProvider', 'signature' => 'Closure($guard = null)', 'this' => 'Illuminate\\Auth\\AuthServiceProvider']]],
                'routeResolver' => ['visibility' => 'protected', 'value' => ['type' => DumpValueTypeEnum::Closure->value, 'value' => ['class' => 'Illuminate\\Routing\\Router', 'signature' => 'Closure()', 'this' => 'Illuminate\\Routing\\Router']]],
            ],
        ];
    }

    #[DataProvider('closureStructuresProvider')]
    public function test_it_parses_closure_structures(
        string $html,
        ?string $expectedSignature,
        ?string $expectedClass,
        ?string $expectedThis,
    ): void {
        // Act

        $result = $this->parser->parse($html);

        // Assert

        $dumps = $result->toArray()['dumps'];

        $this->assertCount(1, $dumps);

        $closureData = $dumps[0]['type'] === DumpValueTypeEnum::Closure->value
            ? $dumps[0]['value']
            : $dumps[0]['value']['items']['callback']['value'];

        $this->assertEquals($expectedSignature, $closureData['signature']);
        $this->assertEquals($expectedClass, $closureData['class']);
        $this->assertEquals($expectedThis, $closureData['this']);
    }

    public static function closureStructuresProvider(): Generator
    {
        yield 'closure with parameter' => [
            'html' => <<<'HTML'
<script> Sfdump = window.Sfdump || (function (doc) { doc.documentElement.classList.add('sf-js-enabled'); var rxEsc = /([.*+?^${}()|\[\]\/\\])/g, idRx = /\bsf-dump-\d+-ref[012]\w+\b/, keyHint = 0 <= navigator.platform.toUpperCase().indexOf('MAC') ? 'Cmd' : 'Ctrl', addEventListener = function (e, n, cb) { e.addEventListener(n, cb, false); }; if (!doc.addEventListener) { addEventListener = function (element, eventName, callback) { element.attachEvent('on' + eventName, function (e) { e.preventDefault = function () {e.returnValue = false;}; e.target = e.srcElement; callback(e); }); }; } function toggle(a, recursive) { var s = a.nextSibling || {}, oldClass = s.className, arrow, newClass; if (/\bsf-dump-compact\b/.test(oldClass)) { arrow = '&#9660;'; newClass = 'sf-dump-expanded'; } else if (/\bsf-dump-expanded\b/.test(oldClass)) { arrow = '&#9654;'; newClass = 'sf-dump-compact'; } else { return false; } if (doc.createEvent && s.dispatchEvent) { var event = doc.createEvent('Event'); event.initEvent('sf-dump-expanded' === newClass ? 'sfbeforedumpexpand' : 'sfbeforedumpcollapse', true, false); s.dispatchEvent(event); } a.lastChild.innerHTML = arrow; s.className = s.className.replace(/\bsf-dump-(compact|expanded)\b/, newClass); if (recursive) { try { a = s.querySelectorAll('.'+oldClass); for (s = 0; s < a.length; ++s) { if (-1 == a[s].className.indexOf(newClass)) { a[s].className = newClass; a[s].previousSibling.lastChild.innerHTML = arrow; } } } catch (e) { } } return true; }; function collapse(a, recursive) { var s = a.nextSibling || {}, oldClass = s.className; if (/\bsf-dump-expanded\b/.test(oldClass)) { toggle(a, recursive); return true; } return false; }; function expand(a, recursive) { var s = a.nextSibling || {}, oldClass = s.className; if (/\bsf-dump-compact\b/.test(oldClass)) { toggle(a, recursive); return true; } return false; }; function collapseAll(root) { var a = root.querySelector('a.sf-dump-toggle'); if (a) { collapse(a, true); expand(a); return true; } return false; } function reveal(node) { var previous, parents = []; while ((node = node.parentNode || {}) && (previous = node.previousSibling) && 'A' === previous.tagName) { parents.push(previous); } if (0 !== parents.length) { parents.forEach(function (parent) { expand(parent); }); return true; } return false; } function highlight(root, activeNode, nodes) { resetHighlightedNodes(root); Array.from(nodes||[]).forEach(function (node) { if (!/\bsf-dump-highlight\b/.test(node.className)) { node.className = node.className + ' sf-dump-highlight'; } }); if (!/\bsf-dump-highlight-active\b/.test(activeNode.className)) { activeNode.className = activeNode.className + ' sf-dump-highlight-active'; } } function resetHighlightedNodes(root) { Array.from(root.querySelectorAll('.sf-dump-str, .sf-dump-key, .sf-dump-public, .sf-dump-protected, .sf-dump-private')).forEach(function (strNode) { strNode.className = strNode.className.replace(/\bsf-dump-highlight\b/, ''); strNode.className = strNode.className.replace(/\bsf-dump-highlight-active\b/, ''); }); } return function (root, x) { root = doc.getElementById(root); var indentRx = new RegExp('^('+(root.getAttribute('data-indent-pad') || ' ').replace(rxEsc, '\\$1')+')+', 'm'), options = {"maxDepth":1,"maxStringLength":160,"fileLinkFormat":false}, elt = root.getElementsByTagName('A'), len = elt.length, i = 0, s, h, t = []; while (i < len) t.push(elt[i++]); for (i in x) { options[i] = x[i]; } function a(e, f) { addEventListener(root, e, function (e, n) { if ('A' == e.target.tagName) { f(e.target, e); } else if ('A' == e.target.parentNode.tagName) { f(e.target.parentNode, e); } else { n = /\bsf-dump-ellipsis\b/.test(e.target.className) ? e.target.parentNode : e.target; if ((n = n.nextElementSibling) && 'A' == n.tagName) { if (!/\bsf-dump-toggle\b/.test(n.className)) { n = n.nextElementSibling || n; } f(n, e, true); } } }); }; function isCtrlKey(e) { return e.ctrlKey || e.metaKey; } function xpathString(str) { var parts = str.match(/[^'"]+|['"]/g).map(function (part) { if ("'" == part) { return '"\'"'; } if ('"' == part) { return "'\"'"; } return "'" + part + "'"; }); return "concat(" + parts.join(",") + ", '')"; } function xpathHasClass(className) { return "contains(concat(' ', normalize-space(@class), ' '), ' " + className +" ')"; } a('mouseover', function (a, e, c) { if (c) { e.target.style.cursor = "pointer"; } }); a('click', function (a, e, c) { if (/\bsf-dump-toggle\b/.test(a.className)) { e.preventDefault(); if (!toggle(a, isCtrlKey(e))) { var r = doc.getElementById(a.getAttribute('href').slice(1)), s = r.previousSibling, f = r.parentNode, t = a.parentNode; t.replaceChild(r, a); f.replaceChild(a, s); t.insertBefore(s, r); f = f.firstChild.nodeValue.match(indentRx); t = t.firstChild.nodeValue.match(indentRx); if (f && t && f[0] !== t[0]) { r.innerHTML = r.innerHTML.replace(new RegExp('^'+f[0].replace(rxEsc, '\\$1'), 'mg'), t[0]); } if (/\bsf-dump-compact\b/.test(r.className)) { toggle(s, isCtrlKey(e)); } } if (c) { } else if (doc.getSelection) { try { doc.getSelection().removeAllRanges(); } catch (e) { doc.getSelection().empty(); } } else { doc.selection.empty(); } } else if (/\bsf-dump-str-toggle\b/.test(a.className)) { e.preventDefault(); e = a.parentNode.parentNode; e.className = e.className.replace(/\bsf-dump-str-(expand|collapse)\b/, a.parentNode.className); } }); elt = root.getElementsByTagName('SAMP'); len = elt.length; i = 0; while (i < len) t.push(elt[i++]); len = t.length; for (i = 0; i < len; ++i) { elt = t[i]; if ('SAMP' == elt.tagName) { a = elt.previousSibling || {}; if ('A' != a.tagName) { a = doc.createElement('A'); a.className = 'sf-dump-ref'; elt.parentNode.insertBefore(a, elt); } else { a.innerHTML += ' '; } a.title = (a.title ? a.title+'\n[' : '[')+keyHint+'+click] Expand all children'; a.innerHTML += elt.className == 'sf-dump-compact' ? '<span>&#9654;</span>' : '<span>&#9660;</span>'; a.className += ' sf-dump-toggle'; x = 1; if ('sf-dump' != elt.parentNode.className) { x += elt.parentNode.getAttribute('data-depth')/1; } } else if (/\bsf-dump-ref\b/.test(elt.className) && (a = elt.getAttribute('href'))) { a = a.slice(1); elt.className += ' sf-dump-hover'; elt.className += ' '+a; if (/[\[{]$/.test(elt.previousSibling.nodeValue)) { a = a != elt.nextSibling.id && doc.getElementById(a); try { s = a.nextSibling; elt.appendChild(a); s.parentNode.insertBefore(a, s); if (/^[@#]/.test(elt.innerHTML)) { elt.innerHTML += ' <span>&#9654;</span>'; } else { elt.innerHTML = '<span>&#9654;</span>'; elt.className = 'sf-dump-ref'; } elt.className += ' sf-dump-toggle'; } catch (e) { if ('&' == elt.innerHTML.charAt(0)) { elt.innerHTML = '&#8230;'; elt.className = 'sf-dump-ref'; } } } } } if (doc.evaluate && Array.from && root.children.length > 1) { root.setAttribute('tabindex', 0); SearchState = function () { this.nodes = []; this.idx = 0; }; SearchState.prototype = { next: function () { if (this.isEmpty()) { return this.current(); } this.idx = this.idx < (this.nodes.length - 1) ? this.idx + 1 : 0; return this.current(); }, previous: function () { if (this.isEmpty()) { return this.current(); } this.idx = this.idx > 0 ? this.idx - 1 : (this.nodes.length - 1); return this.current(); }, isEmpty: function () { return 0 === this.count(); }, current: function () { if (this.isEmpty()) { return null; } return this.nodes[this.idx]; }, reset: function () { this.nodes = []; this.idx = 0; }, count: function () { return this.nodes.length; }, }; function showCurrent(state) { var currentNode = state.current(), currentRect, searchRect; if (currentNode) { reveal(currentNode); highlight(root, currentNode, state.nodes); if ('scrollIntoView' in currentNode) { currentNode.scrollIntoView(true); currentRect = currentNode.getBoundingClientRect(); searchRect = search.getBoundingClientRect(); if (currentRect.top < (searchRect.top + searchRect.height)) { window.scrollBy(0, -(searchRect.top + searchRect.height + 5)); } } } counter.textContent = (state.isEmpty() ? 0 : state.idx + 1) + ' of ' + state.count(); } var search = doc.createElement('div'); search.className = 'sf-dump-search-wrapper sf-dump-search-hidden'; search.innerHTML = ' <input type="text" class="sf-dump-search-input"> <span class="sf-dump-search-count">0 of 0<\/span> <button type="button" class="sf-dump-search-input-previous" tabindex="-1"> <svg viewBox="0 0 1792 1792" xmlns="http://www.w3.org/2000/svg"><path d="M1683 1331l-166 165q-19 19-45 19t-45-19L896 965l-531 531q-19 19-45 19t-45-19l-166-165q-19-19-19-45.5t19-45.5l742-741q19-19 45-19t45 19l742 741q19 19 19 45.5t-19 45.5z"\/><\/svg> <\/button> <button type="button" class="sf-dump-search-input-next" tabindex="-1"> <svg viewBox="0 0 1792 1792" xmlns="http://www.w3.org/2000/svg"><path d="M1683 808l-742 741q-19 19-45 19t-45-19L109 808q-19-19-19-45.5t19-45.5l166-165q19-19 45-19t45 19l531 531 531-531q19-19 45-19t45 19l166 165q19 19 19 45.5t-19 45.5z"\/><\/svg> <\/button> '; root.insertBefore(search, root.firstChild); var state = new SearchState(); var searchInput = search.querySelector('.sf-dump-search-input'); var counter = search.querySelector('.sf-dump-search-count'); var searchInputTimer = 0; var previousSearchQuery = ''; addEventListener(searchInput, 'keyup', function (e) { var searchQuery = e.target.value; /* Don't perform anything if the pressed key didn't change the query */ if (searchQuery === previousSearchQuery) { return; } previousSearchQuery = searchQuery; clearTimeout(searchInputTimer); searchInputTimer = setTimeout(function () { state.reset(); collapseAll(root); resetHighlightedNodes(root); if ('' === searchQuery) { counter.textContent = '0 of 0'; return; } var classMatches = [ "sf-dump-str", "sf-dump-key", "sf-dump-public", "sf-dump-protected", "sf-dump-private", ].map(xpathHasClass).join(' or '); var xpathResult = doc.evaluate('.//span[' + classMatches + '][contains(translate(child::text(), ' + xpathString(searchQuery.toUpperCase()) + ', ' + xpathString(searchQuery.toLowerCase()) + '), ' + xpathString(searchQuery.toLowerCase()) + ')]', root, null, XPathResult.ORDERED_NODE_ITERATOR_TYPE, null); while (node = xpathResult.iterateNext()) state.nodes.push(node); showCurrent(state); }, 400); }); Array.from(search.querySelectorAll('.sf-dump-search-input-next, .sf-dump-search-input-previous')).forEach(function (btn) { addEventListener(btn, 'click', function (e) { e.preventDefault(); -1 !== e.target.className.indexOf('next') ? state.next() : state.previous(); searchInput.focus(); collapseAll(root); showCurrent(state); }) }); addEventListener(root, 'keydown', function (e) { var isSearchActive = !/\bsf-dump-search-hidden\b/.test(search.className); if ((114 === e.keyCode && !isSearchActive) || (isCtrlKey(e) && 70 === e.keyCode)) { /* F3 or CMD/CTRL + F */ if (70 === e.keyCode && document.activeElement === searchInput) { /* * If CMD/CTRL + F is hit while having focus on search input, * the user probably meant to trigger browser search instead. * Let the browser execute its behavior: */ return; } e.preventDefault(); search.className = search.className.replace(/\bsf-dump-search-hidden\b/, ''); searchInput.focus(); } else if (isSearchActive) { if (27 === e.keyCode) { /* ESC key */ search.className += ' sf-dump-search-hidden'; e.preventDefault(); resetHighlightedNodes(root); searchInput.value = ''; } else if ( (isCtrlKey(e) && 71 === e.keyCode) /* CMD/CTRL + G */ || 13 === e.keyCode /* Enter */ || 114 === e.keyCode /* F3 */ ) { e.preventDefault(); e.shiftKey ? state.previous() : state.next(); collapseAll(root); showCurrent(state); } } }); } if (0 >= options.maxStringLength) { return; } try { elt = root.querySelectorAll('.sf-dump-str'); len = elt.length; i = 0; t = []; while (i < len) t.push(elt[i++]); len = t.length; for (i = 0; i < len; ++i) { elt = t[i]; s = elt.innerText || elt.textContent; x = s.length - options.maxStringLength; if (0 < x) { h = elt.innerHTML; elt[elt.innerText ? 'innerText' : 'textContent'] = s.substring(0, options.maxStringLength); elt.className += ' sf-dump-str-collapse'; elt.innerHTML = '<span class=sf-dump-str-collapse>'+h+'<a class="sf-dump-ref sf-dump-str-toggle" title="Collapse"> &#9664;</a></span>'+ '<span class=sf-dump-str-expand>'+elt.innerHTML+'<a class="sf-dump-ref sf-dump-str-toggle" title="'+x+' remaining characters"> &#9654;</a></span>'; } } } catch (e) { } }; })(document); </script><style> .sf-js-enabled pre.sf-dump .sf-dump-compact, .sf-js-enabled .sf-dump-str-collapse .sf-dump-str-collapse, .sf-js-enabled .sf-dump-str-expand .sf-dump-str-expand { display: none; } .sf-dump-hover:hover { background-color: #B729D9; color: #FFF !important; border-radius: 2px; } pre.sf-dump { display: block; white-space: pre; padding: 5px; overflow: initial !important; } pre.sf-dump:after { content: ""; visibility: hidden; display: block; height: 0; clear: both; } pre.sf-dump .sf-dump-ellipsization { display: inline-flex; } pre.sf-dump a { text-decoration: none; cursor: pointer; border: 0; outline: none; color: inherit; } pre.sf-dump img { max-width: 50em; max-height: 50em; margin: .5em 0 0 0; padding: 0; background: url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAAAAAA6mKC9AAAAHUlEQVQY02O8zAABilCaiQEN0EeA8QuUcX9g3QEAAjcC5piyhyEAAAAASUVORK5CYII=) #D3D3D3; } pre.sf-dump .sf-dump-ellipsis { text-overflow: ellipsis; white-space: nowrap; overflow: hidden; } pre.sf-dump .sf-dump-ellipsis-tail { flex-shrink: 0; } pre.sf-dump code { display:inline; padding:0; background:none; } .sf-dump-public.sf-dump-highlight, .sf-dump-protected.sf-dump-highlight, .sf-dump-private.sf-dump-highlight, .sf-dump-str.sf-dump-highlight, .sf-dump-key.sf-dump-highlight { background: rgba(111, 172, 204, 0.3); border: 1px solid #7DA0B1; border-radius: 3px; } .sf-dump-public.sf-dump-highlight-active, .sf-dump-protected.sf-dump-highlight-active, .sf-dump-private.sf-dump-highlight-active, .sf-dump-str.sf-dump-highlight-active, .sf-dump-key.sf-dump-highlight-active { background: rgba(253, 175, 0, 0.4); border: 1px solid #ffa500; border-radius: 3px; } pre.sf-dump .sf-dump-search-hidden { display: none !important; } pre.sf-dump .sf-dump-search-wrapper { font-size: 0; white-space: nowrap; margin-bottom: 5px; display: flex; position: -webkit-sticky; position: sticky; top: 5px; } pre.sf-dump .sf-dump-search-wrapper > * { vertical-align: top; box-sizing: border-box; height: 21px; font-weight: normal; border-radius: 0; background: #FFF; color: #757575; border: 1px solid #BBB; } pre.sf-dump .sf-dump-search-wrapper > input.sf-dump-search-input { padding: 3px; height: 21px; font-size: 12px; border-right: none; border-top-left-radius: 3px; border-bottom-left-radius: 3px; color: #000; min-width: 15px; width: 100%; } pre.sf-dump .sf-dump-search-wrapper > .sf-dump-search-input-next, pre.sf-dump .sf-dump-search-wrapper > .sf-dump-search-input-previous { background: #F2F2F2; outline: none; border-left: none; font-size: 0; line-height: 0; } pre.sf-dump .sf-dump-search-wrapper > .sf-dump-search-input-next { border-top-right-radius: 3px; border-bottom-right-radius: 3px; } pre.sf-dump .sf-dump-search-wrapper > .sf-dump-search-input-next > svg, pre.sf-dump .sf-dump-search-wrapper > .sf-dump-search-input-previous > svg { pointer-events: none; width: 12px; height: 12px; } pre.sf-dump .sf-dump-search-wrapper > .sf-dump-search-count { display: inline-block; padding: 0 5px; margin: 0; border-left: none; line-height: 21px; font-size: 12px; }pre.sf-dump, pre.sf-dump .sf-dump-default{background-color:#18171B; color:#FF8400; line-height:1.2em; font:12px Menlo, Monaco, Consolas, monospace; word-wrap: break-word; white-space: pre-wrap; position:relative; z-index:99999; word-break: break-all}pre.sf-dump .sf-dump-num{font-weight:bold; color:#1299DA}pre.sf-dump .sf-dump-const{font-weight:bold}pre.sf-dump .sf-dump-virtual{font-style:italic}pre.sf-dump .sf-dump-str{font-weight:bold; color:#56DB3A}pre.sf-dump .sf-dump-note{color:#1299DA}pre.sf-dump .sf-dump-ref{color:#A0A0A0}pre.sf-dump .sf-dump-public{color:#FFFFFF}pre.sf-dump .sf-dump-protected{color:#FFFFFF}pre.sf-dump .sf-dump-private{color:#FFFFFF}pre.sf-dump .sf-dump-meta{color:#B729D9}pre.sf-dump .sf-dump-key{color:#56DB3A}pre.sf-dump .sf-dump-index{color:#1299DA}pre.sf-dump .sf-dump-ellipsis{color:#FF8400}pre.sf-dump .sf-dump-ns{user-select:none;}pre.sf-dump .sf-dump-ellipsis-note{color:#1299DA}</style><pre class=sf-dump id=sf-dump-256036757 data-indent-pad="  "><span class=sf-dump-note>Closure(string $value)</span> {<a class=sf-dump-ref>#401</a><samp data-depth=1 class=sf-dump-expanded><span style="color: #A0A0A0;"> // app/Http/Controllers/Api/ProductController.php:16</span>
  <span class=sf-dump-meta>class</span>: "<span class="sf-dump-str sf-dump-ellipsization" title="App\Http\Controllers\Api\ProductController
46 characters"><span class="sf-dump-ellipsis sf-dump-ellipsis-class">App\Http\Controllers\Api</span><span class="sf-dump-ellipsis sf-dump-ellipsis-class">\</span><span class="sf-dump-ellipsis-tail">ProductController</span></span>"
  <span class=sf-dump-meta>this</span>: <span class="sf-dump-note sf-dump-ellipsization" title="App\Http\Controllers\Api\ProductControllerAnotherThis
"><span class="sf-dump-ellipsis sf-dump-ellipsis-note">App\Http\Controllers\Api</span><span class="sf-dump-ellipsis sf-dump-ellipsis-note">\</span><span class="sf-dump-ellipsis-tail">ProductControllerAnotherThis</span></span> {<a class=sf-dump-ref>#359</a> &#8230;}
</samp>}
</pre><script>Sfdump("sf-dump-256036757")</script>
HTML,
            'expectedSignature' => 'Closure(string $value)',
            'expectedClass' => 'App\Http\Controllers\Api\ProductController',
            'expectedThis' => 'App\Http\Controllers\Api\ProductControllerAnotherThis',
        ];

        yield 'closure without parameters' => [
            'html' => <<<'HTML'
<script> Sfdump = window.Sfdump || (function (doc) { doc.documentElement.classList.add('sf-js-enabled'); var rxEsc = /([.*+?^${}()|\[\]\/\\])/g, idRx = /\bsf-dump-\d+-ref[012]\w+\b/, keyHint = 0 <= navigator.platform.toUpperCase().indexOf('MAC') ? 'Cmd' : 'Ctrl', addEventListener = function (e, n, cb) { e.addEventListener(n, cb, false); }; if (!doc.addEventListener) { addEventListener = function (element, eventName, callback) { element.attachEvent('on' + eventName, function (e) { e.preventDefault = function () {e.returnValue = false;}; e.target = e.srcElement; callback(e); }); }; } function toggle(a, recursive) { var s = a.nextSibling || {}, oldClass = s.className, arrow, newClass; if (/\bsf-dump-compact\b/.test(oldClass)) { arrow = '&#9660;'; newClass = 'sf-dump-expanded'; } else if (/\bsf-dump-expanded\b/.test(oldClass)) { arrow = '&#9654;'; newClass = 'sf-dump-compact'; } else { return false; } if (doc.createEvent && s.dispatchEvent) { var event = doc.createEvent('Event'); event.initEvent('sf-dump-expanded' === newClass ? 'sfbeforedumpexpand' : 'sfbeforedumpcollapse', true, false); s.dispatchEvent(event); } a.lastChild.innerHTML = arrow; s.className = s.className.replace(/\bsf-dump-(compact|expanded)\b/, newClass); if (recursive) { try { a = s.querySelectorAll('.'+oldClass); for (s = 0; s < a.length; ++s) { if (-1 == a[s].className.indexOf(newClass)) { a[s].className = newClass; a[s].previousSibling.lastChild.innerHTML = arrow; } } } catch (e) { } } return true; }; function collapse(a, recursive) { var s = a.nextSibling || {}, oldClass = s.className; if (/\bsf-dump-expanded\b/.test(oldClass)) { toggle(a, recursive); return true; } return false; }; function expand(a, recursive) { var s = a.nextSibling || {}, oldClass = s.className; if (/\bsf-dump-compact\b/.test(oldClass)) { toggle(a, recursive); return true; } return false; }; function collapseAll(root) { var a = root.querySelector('a.sf-dump-toggle'); if (a) { collapse(a, true); expand(a); return true; } return false; } function reveal(node) { var previous, parents = []; while ((node = node.parentNode || {}) && (previous = node.previousSibling) && 'A' === previous.tagName) { parents.push(previous); } if (0 !== parents.length) { parents.forEach(function (parent) { expand(parent); }); return true; } return false; } function highlight(root, activeNode, nodes) { resetHighlightedNodes(root); Array.from(nodes||[]).forEach(function (node) { if (!/\bsf-dump-highlight\b/.test(node.className)) { node.className = node.className + ' sf-dump-highlight'; } }); if (!/\bsf-dump-highlight-active\b/.test(activeNode.className)) { activeNode.className = activeNode.className + ' sf-dump-highlight-active'; } } function resetHighlightedNodes(root) { Array.from(root.querySelectorAll('.sf-dump-str, .sf-dump-key, .sf-dump-public, .sf-dump-protected, .sf-dump-private')).forEach(function (strNode) { strNode.className = strNode.className.replace(/\bsf-dump-highlight\b/, ''); strNode.className = strNode.className.replace(/\bsf-dump-highlight-active\b/, ''); }); } return function (root, x) { root = doc.getElementById(root); var indentRx = new RegExp('^('+(root.getAttribute('data-indent-pad') || ' ').replace(rxEsc, '\\$1')+')+', 'm'), options = {"maxDepth":1,"maxStringLength":160,"fileLinkFormat":false}, elt = root.getElementsByTagName('A'), len = elt.length, i = 0, s, h, t = []; while (i < len) t.push(elt[i++]); for (i in x) { options[i] = x[i]; } function a(e, f) { addEventListener(root, e, function (e, n) { if ('A' == e.target.tagName) { f(e.target, e); } else if ('A' == e.target.parentNode.tagName) { f(e.target.parentNode, e); } else { n = /\bsf-dump-ellipsis\b/.test(e.target.className) ? e.target.parentNode : e.target; if ((n = n.nextElementSibling) && 'A' == n.tagName) { if (!/\bsf-dump-toggle\b/.test(n.className)) { n = n.nextElementSibling || n; } f(n, e, true); } } }); }; function isCtrlKey(e) { return e.ctrlKey || e.metaKey; } function xpathString(str) { var parts = str.match(/[^'"]+|['"]/g).map(function (part) { if ("'" == part) { return '"\'"'; } if ('"' == part) { return "'\"'"; } return "'" + part + "'"; }); return "concat(" + parts.join(",") + ", '')"; } function xpathHasClass(className) { return "contains(concat(' ', normalize-space(@class), ' '), ' " + className +" ')"; } a('mouseover', function (a, e, c) { if (c) { e.target.style.cursor = "pointer"; } }); a('click', function (a, e, c) { if (/\bsf-dump-toggle\b/.test(a.className)) { e.preventDefault(); if (!toggle(a, isCtrlKey(e))) { var r = doc.getElementById(a.getAttribute('href').slice(1)), s = r.previousSibling, f = r.parentNode, t = a.parentNode; t.replaceChild(r, a); f.replaceChild(a, s); t.insertBefore(s, r); f = f.firstChild.nodeValue.match(indentRx); t = t.firstChild.nodeValue.match(indentRx); if (f && t && f[0] !== t[0]) { r.innerHTML = r.innerHTML.replace(new RegExp('^'+f[0].replace(rxEsc, '\\$1'), 'mg'), t[0]); } if (/\bsf-dump-compact\b/.test(r.className)) { toggle(s, isCtrlKey(e)); } } if (c) { } else if (doc.getSelection) { try { doc.getSelection().removeAllRanges(); } catch (e) { doc.getSelection().empty(); } } else { doc.selection.empty(); } } else if (/\bsf-dump-str-toggle\b/.test(a.className)) { e.preventDefault(); e = a.parentNode.parentNode; e.className = e.className.replace(/\bsf-dump-str-(expand|collapse)\b/, a.parentNode.className); } }); elt = root.getElementsByTagName('SAMP'); len = elt.length; i = 0; while (i < len) t.push(elt[i++]); len = t.length; for (i = 0; i < len; ++i) { elt = t[i]; if ('SAMP' == elt.tagName) { a = elt.previousSibling || {}; if ('A' != a.tagName) { a = doc.createElement('A'); a.className = 'sf-dump-ref'; elt.parentNode.insertBefore(a, elt); } else { a.innerHTML += ' '; } a.title = (a.title ? a.title+'\n[' : '[')+keyHint+'+click] Expand all children'; a.innerHTML += elt.className == 'sf-dump-compact' ? '<span>&#9654;</span>' : '<span>&#9660;</span>'; a.className += ' sf-dump-toggle'; x = 1; if ('sf-dump' != elt.parentNode.className) { x += elt.parentNode.getAttribute('data-depth')/1; } } else if (/\bsf-dump-ref\b/.test(elt.className) && (a = elt.getAttribute('href'))) { a = a.slice(1); elt.className += ' sf-dump-hover'; elt.className += ' '+a; if (/[\[{]$/.test(elt.previousSibling.nodeValue)) { a = a != elt.nextSibling.id && doc.getElementById(a); try { s = a.nextSibling; elt.appendChild(a); s.parentNode.insertBefore(a, s); if (/^[@#]/.test(elt.innerHTML)) { elt.innerHTML += ' <span>&#9654;</span>'; } else { elt.innerHTML = '<span>&#9654;</span>'; elt.className = 'sf-dump-ref'; } elt.className += ' sf-dump-toggle'; } catch (e) { if ('&' == elt.innerHTML.charAt(0)) { elt.innerHTML = '&#8230;'; elt.className = 'sf-dump-ref'; } } } } } if (doc.evaluate && Array.from && root.children.length > 1) { root.setAttribute('tabindex', 0); SearchState = function () { this.nodes = []; this.idx = 0; }; SearchState.prototype = { next: function () { if (this.isEmpty()) { return this.current(); } this.idx = this.idx < (this.nodes.length - 1) ? this.idx + 1 : 0; return this.current(); }, previous: function () { if (this.isEmpty()) { return this.current(); } this.idx = this.idx > 0 ? this.idx - 1 : (this.nodes.length - 1); return this.current(); }, isEmpty: function () { return 0 === this.count(); }, current: function () { if (this.isEmpty()) { return null; } return this.nodes[this.idx]; }, reset: function () { this.nodes = []; this.idx = 0; }, count: function () { return this.nodes.length; }, }; function showCurrent(state) { var currentNode = state.current(), currentRect, searchRect; if (currentNode) { reveal(currentNode); highlight(root, currentNode, state.nodes); if ('scrollIntoView' in currentNode) { currentNode.scrollIntoView(true); currentRect = currentNode.getBoundingClientRect(); searchRect = search.getBoundingClientRect(); if (currentRect.top < (searchRect.top + searchRect.height)) { window.scrollBy(0, -(searchRect.top + searchRect.height + 5)); } } } counter.textContent = (state.isEmpty() ? 0 : state.idx + 1) + ' of ' + state.count(); } var search = doc.createElement('div'); search.className = 'sf-dump-search-wrapper sf-dump-search-hidden'; search.innerHTML = ' <input type="text" class="sf-dump-search-input"> <span class="sf-dump-search-count">0 of 0<\/span> <button type="button" class="sf-dump-search-input-previous" tabindex="-1"> <svg viewBox="0 0 1792 1792" xmlns="http://www.w3.org/2000/svg"><path d="M1683 1331l-166 165q-19 19-45 19t-45-19L896 965l-531 531q-19 19-45 19t-45-19l-166-165q-19-19-19-45.5t19-45.5l742-741q19-19 45-19t45 19l742 741q19 19 19 45.5t-19 45.5z"\/><\/svg> <\/button> <button type="button" class="sf-dump-search-input-next" tabindex="-1"> <svg viewBox="0 0 1792 1792" xmlns="http://www.w3.org/2000/svg"><path d="M1683 808l-742 741q-19 19-45 19t-45-19L109 808q-19-19-19-45.5t19-45.5l166-165q19-19 45-19t45 19l531 531 531-531q19-19 45-19t45 19l166 165q19 19 19 45.5t-19 45.5z"\/><\/svg> <\/button> '; root.insertBefore(search, root.firstChild); var state = new SearchState(); var searchInput = search.querySelector('.sf-dump-search-input'); var counter = search.querySelector('.sf-dump-search-count'); var searchInputTimer = 0; var previousSearchQuery = ''; addEventListener(searchInput, 'keyup', function (e) { var searchQuery = e.target.value; /* Don't perform anything if the pressed key didn't change the query */ if (searchQuery === previousSearchQuery) { return; } previousSearchQuery = searchQuery; clearTimeout(searchInputTimer); searchInputTimer = setTimeout(function () { state.reset(); collapseAll(root); resetHighlightedNodes(root); if ('' === searchQuery) { counter.textContent = '0 of 0'; return; } var classMatches = [ "sf-dump-str", "sf-dump-key", "sf-dump-public", "sf-dump-protected", "sf-dump-private", ].map(xpathHasClass).join(' or '); var xpathResult = doc.evaluate('.//span[' + classMatches + '][contains(translate(child::text(), ' + xpathString(searchQuery.toUpperCase()) + ', ' + xpathString(searchQuery.toLowerCase()) + '), ' + xpathString(searchQuery.toLowerCase()) + ')]', root, null, XPathResult.ORDERED_NODE_ITERATOR_TYPE, null); while (node = xpathResult.iterateNext()) state.nodes.push(node); showCurrent(state); }, 400); }); Array.from(search.querySelectorAll('.sf-dump-search-input-next, .sf-dump-search-input-previous')).forEach(function (btn) { addEventListener(btn, 'click', function (e) { e.preventDefault(); -1 !== e.target.className.indexOf('next') ? state.next() : state.previous(); searchInput.focus(); collapseAll(root); showCurrent(state); }) }); addEventListener(root, 'keydown', function (e) { var isSearchActive = !/\bsf-dump-search-hidden\b/.test(search.className); if ((114 === e.keyCode && !isSearchActive) || (isCtrlKey(e) && 70 === e.keyCode)) { /* F3 or CMD/CTRL + F */ if (70 === e.keyCode && document.activeElement === searchInput) { /* * If CMD/CTRL + F is hit while having focus on search input, * the user probably meant to trigger browser search instead. * Let the browser execute its behavior: */ return; } e.preventDefault(); search.className = search.className.replace(/\bsf-dump-search-hidden\b/, ''); searchInput.focus(); } else if (isSearchActive) { if (27 === e.keyCode) { /* ESC key */ search.className += ' sf-dump-search-hidden'; e.preventDefault(); resetHighlightedNodes(root); searchInput.value = ''; } else if ( (isCtrlKey(e) && 71 === e.keyCode) /* CMD/CTRL + G */ || 13 === e.keyCode /* Enter */ || 114 === e.keyCode /* F3 */ ) { e.preventDefault(); e.shiftKey ? state.previous() : state.next(); collapseAll(root); showCurrent(state); } } }); } if (0 >= options.maxStringLength) { return; } try { elt = root.querySelectorAll('.sf-dump-str'); len = elt.length; i = 0; t = []; while (i < len) t.push(elt[i++]); len = t.length; for (i = 0; i < len; ++i) { elt = t[i]; s = elt.innerText || elt.textContent; x = s.length - options.maxStringLength; if (0 < x) { h = elt.innerHTML; elt[elt.innerText ? 'innerText' : 'textContent'] = s.substring(0, options.maxStringLength); elt.className += ' sf-dump-str-collapse'; elt.innerHTML = '<span class=sf-dump-str-collapse>'+h+'<a class="sf-dump-ref sf-dump-str-toggle" title="Collapse"> &#9664;</a></span>'+ '<span class=sf-dump-str-expand>'+elt.innerHTML+'<a class="sf-dump-ref sf-dump-str-toggle" title="'+x+' remaining characters"> &#9654;</a></span>'; } } } catch (e) { } }; })(document); </script><style> .sf-js-enabled pre.sf-dump .sf-dump-compact, .sf-js-enabled .sf-dump-str-collapse .sf-dump-str-collapse, .sf-js-enabled .sf-dump-str-expand .sf-dump-str-expand { display: none; } .sf-dump-hover:hover { background-color: #B729D9; color: #FFF !important; border-radius: 2px; } pre.sf-dump { display: block; white-space: pre; padding: 5px; overflow: initial !important; } pre.sf-dump:after { content: ""; visibility: hidden; display: block; height: 0; clear: both; } pre.sf-dump .sf-dump-ellipsization { display: inline-flex; } pre.sf-dump a { text-decoration: none; cursor: pointer; border: 0; outline: none; color: inherit; } pre.sf-dump img { max-width: 50em; max-height: 50em; margin: .5em 0 0 0; padding: 0; background: url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAAAAAA6mKC9AAAAHUlEQVQY02O8zAABilCaiQEN0EeA8QuUcX9g3QEAAjcC5piyhyEAAAAASUVORK5CYII=) #D3D3D3; } pre.sf-dump .sf-dump-ellipsis { text-overflow: ellipsis; white-space: nowrap; overflow: hidden; } pre.sf-dump .sf-dump-ellipsis-tail { flex-shrink: 0; } pre.sf-dump code { display:inline; padding:0; background:none; } .sf-dump-public.sf-dump-highlight, .sf-dump-protected.sf-dump-highlight, .sf-dump-private.sf-dump-highlight, .sf-dump-str.sf-dump-highlight, .sf-dump-key.sf-dump-highlight { background: rgba(111, 172, 204, 0.3); border: 1px solid #7DA0B1; border-radius: 3px; } .sf-dump-public.sf-dump-highlight-active, .sf-dump-protected.sf-dump-highlight-active, .sf-dump-private.sf-dump-highlight-active, .sf-dump-str.sf-dump-highlight-active, .sf-dump-key.sf-dump-highlight-active { background: rgba(253, 175, 0, 0.4); border: 1px solid #ffa500; border-radius: 3px; } pre.sf-dump .sf-dump-search-hidden { display: none !important; } pre.sf-dump .sf-dump-search-wrapper { font-size: 0; white-space: nowrap; margin-bottom: 5px; display: flex; position: -webkit-sticky; position: sticky; top: 5px; } pre.sf-dump .sf-dump-search-wrapper > * { vertical-align: top; box-sizing: border-box; height: 21px; font-weight: normal; border-radius: 0; background: #FFF; color: #757575; border: 1px solid #BBB; } pre.sf-dump .sf-dump-search-wrapper > input.sf-dump-search-input { padding: 3px; height: 21px; font-size: 12px; border-right: none; border-top-left-radius: 3px; border-bottom-left-radius: 3px; color: #000; min-width: 15px; width: 100%; } pre.sf-dump .sf-dump-search-wrapper > .sf-dump-search-input-next, pre.sf-dump .sf-dump-search-wrapper > .sf-dump-search-input-previous { background: #F2F2F2; outline: none; border-left: none; font-size: 0; line-height: 0; } pre.sf-dump .sf-dump-search-wrapper > .sf-dump-search-input-next { border-top-right-radius: 3px; border-bottom-right-radius: 3px; } pre.sf-dump .sf-dump-search-wrapper > .sf-dump-search-input-next > svg, pre.sf-dump .sf-dump-search-wrapper > .sf-dump-search-input-previous > svg { pointer-events: none; width: 12px; height: 12px; } pre.sf-dump .sf-dump-search-wrapper > .sf-dump-search-count { display: inline-block; padding: 0 5px; margin: 0; border-left: none; line-height: 21px; font-size: 12px; }pre.sf-dump, pre.sf-dump .sf-dump-default{background-color:#18171B; color:#FF8400; line-height:1.2em; font:12px Menlo, Monaco, Consolas, monospace; word-wrap: break-word; white-space: pre-wrap; position:relative; z-index:99999; word-break: break-all}pre.sf-dump .sf-dump-num{font-weight:bold; color:#1299DA}pre.sf-dump .sf-dump-const{font-weight:bold}pre.sf-dump .sf-dump-virtual{font-style:italic}pre.sf-dump .sf-dump-str{font-weight:bold; color:#56DB3A}pre.sf-dump .sf-dump-note{color:#1299DA}pre.sf-dump .sf-dump-ref{color:#A0A0A0}pre.sf-dump .sf-dump-public{color:#FFFFFF}pre.sf-dump .sf-dump-protected{color:#FFFFFF}pre.sf-dump .sf-dump-private{color:#FFFFFF}pre.sf-dump .sf-dump-meta{color:#B729D9}pre.sf-dump .sf-dump-key{color:#56DB3A}pre.sf-dump .sf-dump-index{color:#1299DA}pre.sf-dump .sf-dump-ellipsis{color:#FF8400}pre.sf-dump .sf-dump-ns{user-select:none;}pre.sf-dump .sf-dump-ellipsis-note{color:#1299DA}</style><pre class=sf-dump id=sf-dump-256036757 data-indent-pad="  "><span class=sf-dump-note>Closure()</span> {<a class=sf-dump-ref>#401</a><samp data-depth=1 class=sf-dump-expanded><span style="color: #A0A0A0;"></span>
  <span class=sf-dump-meta>class</span>: "<span class="sf-dump-str sf-dump-ellipsization" title="App\Services\PaymentService
46 characters"><span class="sf-dump-ellipsis sf-dump-ellipsis-class">App\Services\PaymentService</span><span class="sf-dump-ellipsis sf-dump-ellipsis-class">\</span><span class="sf-dump-ellipsis-tail">PaymentService</span></span>"
</samp>}
</pre><script>Sfdump("sf-dump-256036757")</script>
HTML,
            'expectedSignature' => 'Closure()',
            'expectedClass' => 'App\Services\PaymentService',
            'expectedThis' => null,
        ];
    }

    #[DataProvider('laravelCommentProvider')]
    public function test_it_extracts_laravel_comments(
        string $html,
        ?string $expectedComment,
    ): void {
        // Act

        $result = $this->parser->parse($html);

        // Assert

        $this->assertEquals($expectedComment, $result->toArray()['source']);
    }

    public static function laravelCommentProvider(): Generator
    {
        yield 'comment with relative path' => [
            'html' => '<pre class=sf-dump><span class=sf-dump-num>42</span><span style="color: #A0A0A0;"> // app/Http/Controllers/UserController.php:25</span></pre>',
            'expectedComment' => 'app/Http/Controllers/UserController.php:25',
        ];

        yield 'comment with absolute path' => [
            'html' => '<pre class=sf-dump><span class=sf-dump-str>test</span><span style="color: #A0A0A0;"> // /var/www/app/Services/PaymentService.php:100</span></pre>',
            'expectedComment' => '/var/www/app/Services/PaymentService.php:100',
        ];

        yield 'no comment present' => [
            'html' => '<pre class=sf-dump><span class=sf-dump-num>42</span></pre>',
            'expectedComment' => null,
        ];
    }

    #[DataProvider('multipleDumpsProvider')]
    public function test_it_parses_multiple_dumps_in_single_output(
        string $html,
        array $expectedValues,
    ): void {
        // Act

        $result = $this->parser->parse($html);

        // Assert

        $dumps = $result->toArray()['dumps'];

        $this->assertJsonStructureMatches(
            $expectedValues,
            $dumps,
        );
    }

    public static function multipleDumpsProvider(): Generator
    {
        yield 'two string dumps' => [
            'html' => <<<'HTML'
<pre class=sf-dump>"<span class=sf-dump-str>first</span>"</pre>
<pre class=sf-dump>"<span class=sf-dump-str>second</span>"</pre>
HTML,
            'expectedValues' => [
                [
                    'type' => DumpValueTypeEnum::String->value,
                    'value' => 'first',
                ],
                [
                    'type' => DumpValueTypeEnum::String->value,
                    'value' => 'second',
                ],
            ],
        ];

        yield 'mixed primitive type dumps' => [
            'html' => <<<'HTML'
<pre class=sf-dump><span class=sf-dump-num>42</span></pre>
<pre class=sf-dump><span class=sf-dump-const>true</span></pre>
<pre class=sf-dump>"<span class=sf-dump-str>text</span>"</pre>
<pre class=sf-dump><span class=sf-dump-const>null</span></pre>
HTML,
            'expectedValues' => [
                [
                    'type' => DumpValueTypeEnum::Number->value,
                    'value' => 42,
                ],
                [
                    'type' => DumpValueTypeEnum::Constant->value,
                    'value' => true,
                ],
                [
                    'type' => DumpValueTypeEnum::String->value,
                    'value' => 'text',
                ],
                [
                    'type' => DumpValueTypeEnum::Constant->value,
                    'value' => null,
                ],
            ],
        ];

        yield 'array and object dumps' => [
            'html' => <<<'HTML'
<pre class=sf-dump><span class=sf-dump-note>array:1</span> [<samp data-depth=1 class=sf-dump-expanded>
  "<span class=sf-dump-key>::key::</span>" => "<span class=sf-dump-str>::value::</span>"
</samp>]</pre>
<pre class=sf-dump><span class=sf-dump-note>stdClass</span> {<a class=sf-dump-ref>#123</a><samp data-depth=1 class=sf-dump-expanded>
  +<span class=sf-dump-public title="Public property">::prop::</span>: <span class=sf-dump-num>1</span>
</samp>}</pre>
HTML,
            'expectedValues' => [
                [
                    'type' => DumpValueTypeEnum::Array->value,
                    'value' => [
                        'items' => [
                            '::key::' => [
                                'type' => DumpValueTypeEnum::String->value,
                                'value' => '::value::',
                            ],
                        ],
                        'length' => 1,
                        'numericallyIndexed' => false,
                    ],
                ],
                [
                    'type' => DumpValueTypeEnum::Object->value,
                    'value' => [
                        'class' => 'stdClass',
                        'propertiesCount' => 1,
                        'properties' => [
                            '::prop::' => [
                                'value' => [
                                    'type' => DumpValueTypeEnum::Number->value,
                                    'value' => 1,
                                ],
                                'visibility' => 'public',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    public function test_it_handles_empty_input(): void
    {
        // Axt

        $result = $this->parser->parse('');

        // Assert

        $this->assertEmpty($result->toArray()['dumps']);
    }

    public function test_it_handles_non_dump_html(): void
    {
        // Act

        $result = $this->parser->parse('<div>No dumps here</div>');

        // Assert

        $this->assertEmpty($result->toArray()['dumps']);
    }

    public function test_it_handles_malformed_html_gracefully(): void
    {
        // Act

        $result = $this->parser->parse('<pre class=sf-dump>malformed');

        // Assert

        $this->assertEmpty($result->toArray()['dumps']);
    }

    #[DataProvider('unknownTypesDataProvider')]
    public function test_it_doesnt_break_with_unknown_types(string $html): void
    {
        // Act

        $result = $this->parser->parse($html)->toArray();

        // Assert

        $this->assertEquals(
            [
                [
                    'type' => DumpValueTypeEnum::Unknown->value,
                    'value' => null,
                ],
            ],
            $result['dumps'],
        );
    }

    public static function unknownTypesDataProvider(): Generator
    {
        yield 'Invalid type' => [
            'html' => <<<'HTML'
<pre class=sf-dump>
    "<span class=sf-dump-gebbrish>::no-value::</span>
</pre>
HTML,
        ];

        yield 'object without class name and ref [broken html]' => [
            'html' => <<<'HTML'
<pre class=sf-dump>{<samp data-depth=1 class=sf-dump-expanded>
  +<span class=sf-dump-public title="Public property">id</span>: <span class=sf-dump-num>1</span>
  +<span class=sf-dump-public title="Public property">name</span>: "<span class=sf-dump-str title="4 characters">John</span>"
</samp>}</pre>
HTML,
        ];
    }

    public function test_it_preserves_array_key_order(): void
    {
        // Arrange

        $html = <<<'HTML'
<pre class=sf-dump><span class=sf-dump-note>array:3</span> [<samp data-depth=1 class=sf-dump-expanded>
  "<span class=sf-dump-key>third</span>" => "<span class=sf-dump-str>3</span>"
  "<span class=sf-dump-key>first</span>" => "<span class=sf-dump-str>1</span>"
  "<span class=sf-dump-key>second</span>" => "<span class=sf-dump-str>2</span>"
</samp>]</pre>
HTML;

        // Act

        $result = $this->parser->parse($html);

        // Assert

        $dump = $result->toArray()['dumps'][0];

        $this->assertEquals(DumpValueTypeEnum::Array->value, $dump['type']);

        $this->assertEquals(
            ['third', 'first', 'second'],
            array_keys($dump['value']['items']),
        );
    }

    public function test_it_handles_deeply_nested_arrays(): void
    {
        // Arrange

        $html = <<<'HTML'
<pre class=sf-dump><span class=sf-dump-note>array:1</span> [<samp data-depth=1 class=sf-dump-expanded>
  "<span class=sf-dump-key>level1</span>" => <span class=sf-dump-note>array:1</span> [<samp data-depth=2 class=sf-dump-compact>
    "<span class=sf-dump-key>level2</span>" => <span class=sf-dump-note>array:1</span> [<samp data-depth=3 class=sf-dump-compact>
      "<span class=sf-dump-key>level3</span>" => "<span class=sf-dump-str>deep</span>"
    </samp>]
  </samp>]
</samp>]</pre>
HTML;

        // Act

        $result = $this->parser->parse($html);

        // Assert

        $dump = $result->toArray()['dumps'][0];

        $this->assertEquals(DumpValueTypeEnum::Array->value, $dump['type']);

        $level3Value = $dump['value']['items']['level1']['value']['items']['level2']['value']['items']['level3']['value'];
        $this->assertEquals('deep', $level3Value);
    }

    public function test_it_handles_mixed_array_keys(): void
    {
        // Arrange

        $html = <<<'HTML'
<pre class=sf-dump><span class=sf-dump-note>array:3</span> [<samp data-depth=1 class=sf-dump-expanded>
  <span class=sf-dump-index>0</span> => "<span class=sf-dump-str>indexed</span>"
  "<span class=sf-dump-key>string_key</span>" => "<span class=sf-dump-str>associative</span>"
  <span class=sf-dump-index>2</span> => "<span class=sf-dump-str>another</span>"
</samp>]</pre>
HTML;

        // Act

        $result = $this->parser->parse($html);

        // Assert

        $dump = $result->toArray()['dumps'][0];

        $this->assertEquals(DumpValueTypeEnum::Array->value, $dump['type']);

        $this->assertArrayHasKey(0, $dump['value']['items']);
        $this->assertArrayHasKey('string_key', $dump['value']['items']);
        $this->assertArrayHasKey(2, $dump['value']['items']);
    }

    /*
     * Asserts.
     */

    private function assertJsonStructureMatches(array $expected, array $actual): void
    {
        $actualJson = json_encode($actual, JSON_PRETTY_PRINT);
        $expectedJson = json_encode($expected, JSON_PRETTY_PRINT);

        $this->assertJsonStringEqualsJsonString($expectedJson, $actualJson);
    }
}

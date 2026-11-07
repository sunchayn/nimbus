<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Magic\Toolbox\Actions;

use Illuminate\Cookie\CookieValuePrefix;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Session;
use PHPUnit\Framework\Attributes\CoversClass;
use Sunchayn\Nimbus\Modules\Magic\Toolbox\Actions\ReadSessionAction;
use Sunchayn\Nimbus\Modules\Magic\Toolbox\Exceptions\InvalidToolInputException;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(ReadSessionAction::class)]
class ReadSessionActionFunctionalTest extends TestCase
{
    public function test_it_reads_a_session_by_its_id(): void
    {
        // Arrange

        config()->set('session.driver', 'array');

        $id = str_repeat('a', 40);

        Session::driver()->getHandler()->write($id, serialize(['user' => 'jane']));

        $action = resolve(ReadSessionAction::class);

        // Act

        $session = $action->execute($id);

        // Assert

        $this->assertSame(['user' => 'jane'], $session);
    }

    public function test_it_reads_a_session_by_its_encrypted_cookie(): void
    {
        // Arrange

        config()->set('session.driver', 'array');

        $id = str_repeat('b', 40);

        Session::driver()->getHandler()->write($id, serialize(['user' => 'jane']));

        $cookie = Crypt::encryptString(CookieValuePrefix::create(config('session.cookie'), Crypt::getKey()).$id);

        $action = resolve(ReadSessionAction::class);

        // Act

        $session = $action->execute($cookie);

        // Assert

        $this->assertSame('jane', $session['user']);
    }

    public function test_it_reads_a_session_stored_as_json(): void
    {
        // Arrange

        config()->set('session.driver', 'array');
        config()->set('session.serialization', 'json');

        $id = str_repeat('d', 40);

        Session::driver()->getHandler()->write($id, (string) json_encode(['user' => 'jane']));

        $action = resolve(ReadSessionAction::class);

        // Act

        $session = $action->execute($id);

        // Assert

        $this->assertSame(['user' => 'jane'], $session);
    }

    public function test_it_reads_an_encrypted_session(): void
    {
        // Arrange

        config()->set('session.driver', 'array');
        config()->set('session.encrypt', true);

        $id = str_repeat('e', 40);

        Session::driver()->getHandler()->write($id, Crypt::encryptString(serialize(['user' => 'jane'])));

        $action = resolve(ReadSessionAction::class);

        // Act

        $session = $action->execute($id);

        // Assert

        $this->assertSame(['user' => 'jane'], $session);
    }

    public function test_it_breaks_when_no_session_is_stored(): void
    {
        // Arrange

        config()->set('session.driver', 'array');

        $action = resolve(ReadSessionAction::class);

        // Anticipate

        $this->expectException(InvalidToolInputException::class);
        $this->expectExceptionMessage('No stored session matches this value.');

        // Act

        $action->execute(str_repeat('f', 40));
    }

    public function test_it_breaks_when_the_stored_payload_cannot_be_decoded(): void
    {
        // Arrange

        config()->set('session.driver', 'array');

        $id = str_repeat('g', 40);

        Session::driver()->getHandler()->write($id, 'not a serialized payload');

        $action = resolve(ReadSessionAction::class);

        // Anticipate

        $this->expectException(InvalidToolInputException::class);

        // Act

        $action->execute($id);
    }

    public function test_it_never_instantiates_objects_stored_in_the_session(): void
    {
        // Arrange

        config()->set('session.driver', 'array');

        SessionPayloadObject::$wokenUp = false;

        $id = str_repeat('c', 40);

        Session::driver()->getHandler()->write($id, serialize(['user' => 'jane', 'object' => new SessionPayloadObject]));

        SessionPayloadObject::$wokenUp = false;

        $action = resolve(ReadSessionAction::class);

        // Act

        $session = $action->execute($id);

        // Assert

        $this->assertFalse(SessionPayloadObject::$wokenUp);
        $this->assertSame('jane', $session['user']);
        $this->assertSame(SessionPayloadObject::class, $session['object']['__PHP_Incomplete_Class_Name']);
    }
}

class SessionPayloadObject
{
    public static bool $wokenUp = false;

    public string $secret = 'value';

    public function __wakeup(): void
    {
        self::$wokenUp = true;
    }
}

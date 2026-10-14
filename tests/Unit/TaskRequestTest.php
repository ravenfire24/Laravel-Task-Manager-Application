<?php

namespace Tests\Unit;

use App\Http\Requests\TaskRequest;
use App\Models\User;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Factory;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class TaskRequestTest extends TestCase
{
    #[DataProvider('validTasks')]
    public function test_valid_task_values_are_preserved(array $input): void
    {
        $this->assertSame($input, $this->validator($input)->validate());
    }

    public static function validTasks(): array
    {
        return [
            'pending without description' => [['title' => 'Review proposal', 'status' => 'pending']],
            'completed with null description' => [['title' => 'Review proposal', 'description' => null, 'status' => 'completed']],
            'minimum title and empty description' => [['title' => 'A', 'description' => '', 'status' => 'pending']],
            'maximum ASCII lengths' => [['title' => str_repeat('a', 255), 'description' => str_repeat('x', 10000), 'status' => 'completed']],
            'maximum Unicode lengths' => [['title' => str_repeat('é', 255), 'description' => str_repeat('界', 10000), 'status' => 'pending']],
        ];
    }

    #[DataProvider('invalidTasks')]
    public function test_invalid_input_throws_validation_exception_for_the_affected_field(array $input, string $field): void
    {
        try {
            $this->validator($input)->validate();
            $this->fail('Invalid task input must not validate.');
        } catch (ValidationException $exception) {
            $this->assertSame(422, $exception->status);
            $this->assertSame([$field], array_keys($exception->errors()));
        }
    }

    public static function invalidTasks(): array
    {
        $valid = ['title' => 'Review proposal', 'status' => 'pending'];

        return [
            'missing title' => [['status' => 'pending'], 'title'],
            'empty title' => [[...$valid, 'title' => ''], 'title'],
            'whitespace title' => [[...$valid, 'title' => '   '], 'title'],
            'null title' => [[...$valid, 'title' => null], 'title'],
            'array title' => [[...$valid, 'title' => ['unexpected']], 'title'],
            'numeric title' => [[...$valid, 'title' => 123], 'title'],
            'title one character too long' => [[...$valid, 'title' => str_repeat('a', 256)], 'title'],
            'Unicode title one character too long' => [[...$valid, 'title' => str_repeat('é', 256)], 'title'],
            'array description' => [[...$valid, 'description' => ['unexpected']], 'description'],
            'description one character too long' => [[...$valid, 'description' => str_repeat('x', 10001)], 'description'],
            'missing status' => [['title' => 'Review proposal'], 'status'],
            'null status' => [[...$valid, 'status' => null], 'status'],
            'array status' => [[...$valid, 'status' => ['pending']], 'status'],
            'unsupported status' => [[...$valid, 'status' => 'archived'], 'status'],
            'wrong status casing' => [[...$valid, 'status' => 'Pending'], 'status'],
        ];
    }

    public function test_validation_excludes_server_managed_and_unknown_fields(): void
    {
        $allowed = ['title' => 'Review proposal', 'description' => 'Check scope', 'status' => 'pending'];
        $input = [...$allowed, 'id' => 999, 'position' => -999, 'created_at' => '2000-01-01', 'unexpected' => 'ignored'];

        $this->assertSame($allowed, $this->validator($input)->validate());
    }

    public function test_shared_demo_authorizes_requests_without_an_application_user(): void
    {
        $request = new TaskRequest;
        $request->setUserResolver(fn () => null);

        $this->assertNull($request->user());
        $this->assertTrue($request->authorize());
    }

    public function test_shared_demo_authorizes_requests_with_an_application_user(): void
    {
        $request = new TaskRequest;
        $user = new User;
        $request->setUserResolver(fn () => $user);

        $this->assertSame($user, $request->user());
        $this->assertTrue($request->authorize());
    }

    private function validator(array $input): Validator
    {
        $factory = new Factory(new Translator(new ArrayLoader, 'en'));

        return $factory->make($input, (new TaskRequest)->rules());
    }
}

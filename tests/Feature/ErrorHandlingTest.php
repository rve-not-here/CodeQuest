<?php

namespace Tests\Feature;

use Illuminate\Database\QueryException;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;
use Tests\TestCase;

class ErrorHandlingTest extends TestCase
{
    public function test_failed_query_does_not_put_bound_student_input_in_exception_message(): void
    {
        try {
            DB::select('SELECT ? FROM codequest_missing_table', ['private-student-code']);
            $this->fail('The query should fail.');
        } catch (QueryException $exception) {
            $this->assertStringNotContainsString('private-student-code', $exception->getMessage());
        }
    }

    public function test_unexpected_failure_returns_safe_json_with_debug_disabled(): void
    {
        config()->set('app.debug', false);

        Route::get('/_error-probe', fn () => throw new \RuntimeException('private database detail'));

        $this->getJson('/_error-probe')
            ->assertInternalServerError()
            ->assertJson(['message' => 'Server Error'])
            ->assertDontSee('private database detail');
    }

    public function test_database_failure_logs_safe_request_context_once_and_returns_safe_json(): void
    {
        config()->set('app.debug', false);

        $logger = new class extends AbstractLogger
        {
            /** @var list<array{message: string, context: array<string, mixed>}> */
            public array $records = [];

            public function log($level, string|\Stringable $message, array $context = []): void
            {
                $this->records[] = ['message' => (string) $message, 'context' => $context];
            }
        };
        app()->instance(LoggerInterface::class, $logger);

        Route::get('/_query-error-probe', fn () => DB::select('SELECT ? FROM codequest_missing_table', ['private-student-code']))
            ->name('error.query-probe');

        $this->getJson('/_query-error-probe')
            ->assertInternalServerError()
            ->assertJson(['message' => 'Server Error'])
            ->assertDontSee('private-student-code');

        $this->assertCount(1, $logger->records);
        $this->assertSame('error.query-probe', $logger->records[0]['context']['route']);
        $this->assertSame('GET', $logger->records[0]['context']['method']);
        $this->assertStringNotContainsString('private-student-code', $logger->records[0]['message']);
    }

    public function test_json_authentication_validation_and_missing_page_errors_keep_http_statuses(): void
    {
        $this->getJson(route('dashboard'))->assertUnauthorized()->assertJsonStructure(['message']);
        $this->postJson(route('login'), [])->assertUnprocessable()->assertJsonValidationErrors(['username', 'password']);
        $this->getJson('/no-such-codequest-page')->assertNotFound()->assertJsonStructure(['message']);
    }

    public function test_validation_preserves_username_without_flashing_password_or_csrf_token(): void
    {
        $this->post(route('login'), [
            'username' => 'returning-student',
            'password' => '',
            '_token' => 'private-csrf-token',
        ])->assertSessionHasErrors('password');

        $this->assertSame('returning-student', session()->getOldInput('username'));
        $this->assertNull(session()->getOldInput('password'));
        $this->assertNull(session()->getOldInput('_token'));
    }

    public function test_session_expiry_returns_a_safe_419_page(): void
    {
        Route::get('/_expired-session-probe', fn () => throw new TokenMismatchException);

        $this->get('/_expired-session-probe')
            ->assertStatus(419)
            ->assertSee('Open the page again, then retry your action.');
    }

    public function test_rate_limiter_returns_a_recoverable_429_page(): void
    {
        Route::get('/_rate-probe', fn () => 'ready')->middleware('throttle:1,1');

        $this->get('/_rate-probe')->assertOk();

        $this->get('/_rate-probe')
            ->assertTooManyRequests()
            ->assertSee('Please wait a moment before trying again.');
    }

    public function test_html_error_pages_explain_recovery_without_rendering_exception_details(): void
    {
        config()->set('app.debug', false);

        Route::get('/_error-probe/{status}', fn (int $status) => abort($status, 'private runtime detail'));

        foreach ([403, 404, 419, 429, 500, 503] as $status) {
            $this->get('/_error-probe/'.$status)
                ->assertStatus($status)
                ->assertSee('Return to start')
                ->assertDontSee('private runtime detail');
        }
    }
}

<?php

declare(strict_types=1);

namespace WPPillar\Framework\Http;

use WP_User;

/**
 * Abstract base controller — all plugin controllers extend this class.
 *
 * The Request instance is injected by the Router on every call.
 * Use $this->request for input; use Response static methods for output.
 *
 * Usage in child controllers:
 *   class TicketController extends Controller
 *   {
 *       public function index(Request $request): \WP_REST_Response
 *       {
 *           return Response::paginated(Ticket::paginate(25));
 *       }
 *   }
 */
abstract class Controller
{
    protected Request $request;

    /** Fully-qualified Response class for static access in child controllers. */
    protected string $response = Response::class;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * Validate the current request against the given rules.
     * Delegates to Request::validate() — throws WP_Error (422) on failure.
     *
     * @throws ValidationException on failure (auto-caught by Router → 422 response).
     */
    public function validate(array $rules): array
    {
        return $this->request->validate($rules);
    }

    /**
     * Get the currently authenticated WordPress user.
     */
    public function currentUser(): WP_User
    {
        return $this->request->user();
    }

    /**
     * Get the ID of the currently authenticated WordPress user.
     */
    public function currentUserId(): int
    {
        return $this->request->userId();
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ExampleModel;
use WP_REST_Response;
use WPPillar\Framework\Http\Controller;
use WPPillar\Framework\Http\Request;
use WPPillar\Framework\Http\Response;

/**
 * CRUD controller for the Example resource.
 *
 * Demonstrates the full WP Pillar controller pattern:
 * - Extends Controller (Request injected, validate() delegated)
 * - Always uses ->with([]) for eager loading (add relation names when defined)
 * - Always uses Response static methods — never raw WP_REST_Response
 * - index() always paginates — never returns unbounded collections
 */
class ExampleController extends Controller
{
    /**
     * GET /examples — paginated list of all examples.
     */
    public function index(Request $request): WP_REST_Response
    {
        // Always eager-load relationships — add names here when defined on the model.
        $examples = ExampleModel::with([])->paginate(25);

        return Response::paginated($examples);
    }

    /**
     * POST /examples — create a new example.
     */
    public function store(Request $request): WP_REST_Response
    {
        $data = $this->validate([
            'name'   => 'required|string|max:255',
            'email'  => 'required|email|max:255',
            'status' => 'nullable|in:active,inactive',
        ]);

        $example = ExampleModel::create([
            'name'       => $data['name'],
            'email'      => $data['email'],
            'status'     => $data['status'] ?? 'active',
            'wp_user_id' => $this->currentUserId(),
        ]);

        return Response::success($example, __('Example created successfully.', 'example-plugin'), 201);
    }

    /**
     * GET /examples/{id} — retrieve a single example.
     */
    public function show(Request $request): WP_REST_Response
    {
        $id      = (int) $request->input('id');
        $example = ExampleModel::with([])->find($id);

        if (!$example) {
            return Response::notFound(__('Example not found.', 'example-plugin'));
        }

        return Response::success($example);
    }

    /**
     * PUT /examples/{id} — replace an example.
     */
    public function update(Request $request): WP_REST_Response
    {
        $id      = (int) $request->input('id');
        $example = ExampleModel::find($id);

        if (!$example) {
            return Response::notFound(__('Example not found.', 'example-plugin'));
        }

        $data = $this->validate([
            'name'   => 'nullable|string|max:255',
            'email'  => 'nullable|email|max:255',
            'status' => 'nullable|in:active,inactive',
        ]);

        $example->update(array_filter($data, static fn($v) => $v !== null));

        return Response::success($example->fresh(), __('Example updated successfully.', 'example-plugin'));
    }

    /**
     * DELETE /examples/{id} — remove an example.
     */
    public function destroy(Request $request): WP_REST_Response
    {
        $id      = (int) $request->input('id');
        $example = ExampleModel::find($id);

        if (!$example) {
            return Response::notFound(__('Example not found.', 'example-plugin'));
        }

        $example->delete();

        return Response::success(null, __('Example deleted successfully.', 'example-plugin'));
    }
}

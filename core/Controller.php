<?php
namespace SpotCore;

/** Shared helpers for every controller. */
abstract class Controller
{
    protected function view(string $view, array $data = [], int $status = 200, string $layout = 'layouts/main'): Response
    {
        return Response::html(View::render($view, $data, $layout), $status);
    }

    protected function redirect(string $path): Response
    {
        return Response::redirect($path);
    }

    /**
     * Validates $data. On failure it flashes the errors and the old input and
     * returns a redirect; on success it returns null and the controller continues.
     */
    protected function validate(array $data, array $rules, string $backTo, array $labels = []): ?Response
    {
        $validator = new Validator($data, $rules, $labels);
        if ($validator->passes()) {
            return null;
        }
        Session::flash('errors', $validator->errors());
        Session::flash('old', $data);
        Session::flash('error', 'Please fix the highlighted fields.');
        return Response::redirect($backTo);
    }
}

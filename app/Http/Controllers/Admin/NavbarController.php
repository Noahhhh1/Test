<?php

namespace Azuriom\Http\Controllers\Admin;

use Azuriom\Http\Controllers\Controller;
use Azuriom\Http\Requests\NavbarElementRequest;
use Azuriom\Models\NavbarElement;
use Azuriom\Models\Page;
use Azuriom\Models\Post;
use Azuriom\Models\Role;
use Illuminate\Http\Request;

class NavbarController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $elements = NavbarElement::with('elements')
            ->scopes('parent')
            ->orderBy('position')
            ->get();

        return view('admin.navbar-elements.index', ['navbarElements' => $elements]);
    }

    /**
     * Update the resources order in storage.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function updateOrder(Request $request)
    {
        $this->validate($request, [
            'order.*.id' => ['required', 'integer'],
            'order.*.children' => ['sometimes', 'array'],
        ]);

        $elements = $request->input('order');

        $position = 1;

        foreach ($elements as $element) {
            $id = $element['id'];
            $children = $element['children'] ?? [];

            NavbarElement::whereKey($id)->update([
                'position' => $position++,
                'parent_id' => null,
            ]);

            $childPosition = 1;

            foreach ($children as $child) {
                NavbarElement::whereKey($child['id'])->update([
                    'position' => $childPosition++,
                    'parent_id' => $id,
                ]);
            }
        }

        NavbarElement::clearCache();

        return response()->json([
            'message' => trans('admin.navbar_elements.updated'),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.navbar-elements.create', [
            'types' => NavbarElement::types(),
            'pages' => Page::enabled()->get(),
            'posts' => Post::published()->get(),
            'roles' => Role::orderByDesc('power')->get(),
            'pluginRoutes' => plugins()->getRouteDescriptions(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(NavbarElementRequest $request)
    {
        $element = NavbarElement::create($request->validated());

        $element->roles()->sync($request->input('roles'));

        return to_route('admin.navbar-elements.index')
            ->with('success', trans('messages.status.success'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(NavbarElement $navbarElement)
    {
        return view('admin.navbar-elements.edit', [
            'navbarElement' => $navbarElement,
            'types' => NavbarElement::types(),
            'pages' => Page::enabled()->get(),
            'posts' => Post::published()->get(),
            'roles' => Role::orderByDesc('power')->get(),
            'pluginRoutes' => plugins()->getRouteDescriptions(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(NavbarElementRequest $request, NavbarElement $navbarElement)
    {
        if ($navbarElement->isDropdown() && $request->input('type') !== 'dropdown') {
            foreach ($navbarElement->elements as $element) {
                $element->parent()->dissociate();
                $element->save();
            }
        }

        $navbarElement->update($request->validated());

        $navbarElement->roles()->sync($request->input('roles'));

        NavbarElement::clearCache();

        return to_route('admin.navbar-elements.index')
            ->with('success', trans('messages.status.success'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @throws \LogicException
     */
    public function destroy(NavbarElement $navbarElement)
    {
        if ($navbarElement->isDropdown() && ! $navbarElement->elements->isEmpty()) {
            return to_route('admin.navbar-elements.index')
                ->with('error', trans('admin.navbar_elements.not_empty'));
        }

        $navbarElement->delete();

        return to_route('admin.navbar-elements.index')
            ->with('success', trans('messages.status.success'));
    }
}

<?php

namespace App\Admin\Controllers;

use OpenAdmin\Admin\Controllers\AdminController;
use OpenAdmin\Admin\Form;
use OpenAdmin\Admin\Grid;
use OpenAdmin\Admin\Show;
use \App\Models\VehicleType;

class VehicleTypeController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Vehicle Type';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new VehicleType());

        $grid->column('id', __('ID'))->sortable();
        $grid->column('type', __('Type'))->sortable();
        $grid->column('name', __('Name'))->sortable();
        $grid->column('seating_capacity', __('Seating Capacity'));
        $grid->column('starting_price', __('Starting Price'))->display(function ($price) {
            return number_format($price, 2);
        });
        $grid->column('rate_per_km', __('Rate per KM'))->display(function ($rate) {
            return number_format($rate, 2);
        });
        $grid->column('image', __('Image'))->display(function ($image) {
            return $image ? '<img src="' . asset($image) . '" style="width:50px; height:auto;" />' : 'N/A';
        });
        $grid->column('created_at', __('Created At'));
        $grid->column('updated_at', __('Updated At'));

        return $grid;
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     * @return Show
     */
    protected function detail($id)
    {
        $show = new Show(VehicleType::findOrFail($id));

        $show->field('id', __('ID'));
        $show->field('type', __('Type'));
        $show->field('name', __('Name'));
        $show->field('seating_capacity', __('Seating Capacity'));
        $show->field('starting_price', __('Starting Price'))->as(function ($price) {
            return number_format($price, 2);
        });
        $show->field('rate_per_km', __('Rate per KM'))->as(function ($rate) {
            return number_format($rate, 2);
        });
        $show->field('image', __('Image'))->as(function ($image) {
            return $image ? '<img src="' . asset($image) . '" style="width:100px; height:auto;" />' : 'N/A';
        })->unescape(); // Allow HTML to be rendered
        $show->field('created_at', __('Created At'));
        $show->field('updated_at', __('Updated At'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new VehicleType());

        $form->text('type', __('Type'))->unique();
        $form->text('name', __('Name'));
        $form->number('seating_capacity', __('Seating Capacity'));
        $form->decimal('starting_price', __('Starting Price'), 10, 2);
        $form->decimal('rate_per_km', __('Rate per KM'), 8, 2);
        $form->image('image', __('Image'))->removable(); 

        return $form;
    }
}

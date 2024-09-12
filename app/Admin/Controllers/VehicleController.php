<?php

namespace App\Admin\Controllers;

use OpenAdmin\Admin\Controllers\AdminController;
use OpenAdmin\Admin\Form;
use OpenAdmin\Admin\Grid;
use OpenAdmin\Admin\Show;
use \App\Models\Vehicle;

class VehicleController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Vehicle';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Vehicle());

        $grid->column('id', __('Id'));
        $grid->column('driver_id', __('Driver id'));
        $grid->column('vehicle_type', __('Vehicle type'));
        $grid->column('initial_starting_price', __('Initial starting price'));
        $grid->column('rate_per_km', __('Rate per km'));
        $grid->column('license_plate', __('License plate'));
        $grid->column('seating_capacity', __('Seating capacity'));
        $grid->column('image', __('Image'));
        $grid->column('created_at', __('Created at'));
        $grid->column('updated_at', __('Updated at'));
        $grid->column('deleted_at', __('Deleted at'));

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
        $show = new Show(Vehicle::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('driver_id', __('Driver id'));
        $show->field('vehicle_type', __('Vehicle type'));
        $show->field('initial_starting_price', __('Initial starting price'));
        $show->field('rate_per_km', __('Rate per km'));
        $show->field('license_plate', __('License plate'));
        $show->field('seating_capacity', __('Seating capacity'));
        $show->field('image', __('Image'));
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));
        $show->field('deleted_at', __('Deleted at'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Vehicle());

        $form->number('driver_id', __('Driver id'));
        $form->text('vehicle_type', __('Vehicle type'));
        $form->decimal('initial_starting_price', __('Initial starting price'));
        $form->decimal('rate_per_km', __('Rate per km'));
        $form->text('license_plate', __('License plate'));
        $form->number('seating_capacity', __('Seating capacity'));
        $form->image('image', __('Image'));

        return $form;
    }
}

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
        $grid->column('driver_id', __('Driver ID'));
        $grid->column('vehicle_type_id', __('Vehicle Type ID'));
        $grid->column('brand', __('Brand'));
        $grid->column('model', __('Model'));
        $grid->column('color', __('Color'));
        $grid->column('license_plate', __('License Plate'));
        $grid->column('created_at', __('Created At'));
        $grid->column('updated_at', __('Updated At'));
        $grid->column('deleted_at', __('Deleted At'));

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
        $show->field('driver_id', __('Driver ID'));
        $show->field('vehicle_type_id', __('Vehicle Type ID'));
        $show->field('brand', __('Brand'));
        $show->field('model', __('Model'));
        $show->field('color', __('Color'));
        $show->field('license_plate', __('License Plate'));
        $show->field('created_at', __('Created At'));
        $show->field('updated_at', __('Updated At'));
        $show->field('deleted_at', __('Deleted At'));

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

        $form->number('driver_id', __('Driver ID'));
        $form->number('vehicle_type_id', __('Vehicle Type ID'));
        $form->text('brand', __('Brand'));
        $form->text('model', __('Model'));
        $form->text('color', __('Color'));
        $form->text('license_plate', __('License Plate'));

        return $form;
    }
}

<?php

namespace App\Admin\Controllers;

use OpenAdmin\Admin\Controllers\AdminController;
use OpenAdmin\Admin\Form;
use OpenAdmin\Admin\Grid;
use OpenAdmin\Admin\Show;
use \App\Models\Driver;

class DriverController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Driver';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Driver());

        $grid->column('id', __('Id'));
        $grid->column('user_id', __('User id'));
        $grid->column('license_no', __('License no'));
        $grid->column('rating', __('Rating'));
        $grid->column('available', __('Available'));
        $grid->column('latitude', __('Latitude'));
        $grid->column('longitude', __('Longitude'));
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
        $show = new Show(Driver::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('user_id', __('User id'));
        $show->field('license_no', __('License no'));
        $show->field('rating', __('Rating'));
        $show->field('available', __('Available'));
        $show->field('latitude', __('Latitude'));
        $show->field('longitude', __('Longitude'));
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
        $form = new Form(new Driver());

        $form->number('user_id', __('User id'));
        $form->text('license_no', __('License no'));
        $form->decimal('rating', __('Rating'))->default(5.0);
        $form->switch('available', __('Available'));
        $form->decimal('latitude', __('Latitude'));
        $form->decimal('longitude', __('Longitude'));

        return $form;
    }
}

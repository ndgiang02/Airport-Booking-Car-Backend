<?php

namespace App\Admin\Controllers;

use OpenAdmin\Admin\Controllers\AdminController;
use OpenAdmin\Admin\Form;
use OpenAdmin\Admin\Grid;
use OpenAdmin\Admin\Show;
use \App\Models\TripBooking;

class TripBookingController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'TripBooking';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new TripBooking());

        $grid->column('id', __('Id'));
        $grid->column('driver_id', __('Driver id'));
        $grid->column('customer_id', __('Customer id'));
        $grid->column('from_address', __('From address'));
        $grid->column('from_lat', __('From lat'));
        $grid->column('from_lng', __('From lng'));
        $grid->column('to_address', __('To address'));
        $grid->column('to_lat', __('To lat'));
        $grid->column('to_lng', __('To lng'));
        $grid->column('from_date_time', __('From date time'));
        $grid->column('to_date_time', __('To date time'));
        $grid->column('km', __('Km'));
        $grid->column('total_amount', __('Total amount'));
        $grid->column('payment', __('Payment'));
        $grid->column('trip_status', __('Trip status'));
        $grid->column('trip_type', __('Trip type'));
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
        $show = new Show(TripBooking::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('driver_id', __('Driver id'));
        $show->field('customer_id', __('Customer id'));
        $show->field('from_address', __('From address'));
        $show->field('from_lat', __('From lat'));
        $show->field('from_lng', __('From lng'));
        $show->field('to_address', __('To address'));
        $show->field('to_lat', __('To lat'));
        $show->field('to_lng', __('To lng'));
        $show->field('from_date_time', __('From date time'));
        $show->field('to_date_time', __('To date time'));
        $show->field('km', __('Km'));
        $show->field('total_amount', __('Total amount'));
        $show->field('payment', __('Payment'));
        $show->field('trip_status', __('Trip status'));
        $show->field('trip_type', __('Trip type'));
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
        $form = new Form(new TripBooking());

        $form->number('driver_id', __('Driver id'));
        $form->number('customer_id', __('Customer id'));
        $form->text('from_address', __('From address'));
        $form->decimal('from_lat', __('From lat'));
        $form->decimal('from_lng', __('From lng'));
        $form->text('to_address', __('To address'));
        $form->decimal('to_lat', __('To lat'));
        $form->decimal('to_lng', __('To lng'));
        $form->datetime('from_date_time', __('From date time'))->default(date('Y-m-d H:i:s'));
        $form->datetime('to_date_time', __('To date time'))->default(date('Y-m-d H:i:s'));
        $form->number('km', __('Km'));
        $form->decimal('total_amount', __('Total amount'));
        $form->text('payment', __('Payment'));
        $form->text('trip_status', __('Trip status'));
        $form->text('trip_type', __('Trip type'))->default('airport');

        return $form;
    }
}

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
        $grid->column('scheduled_time', __('Scheduled time'))->display(function ($scheduled_time) {
            return $scheduled_time ? \Carbon\Carbon::parse($scheduled_time)->format('d-m-Y H:i') : 'N/A';
        });
        
        $grid->column('from_time', __('From time'))->display(function ($from_time) {
            return $from_time ? \Carbon\Carbon::parse($from_time)->format('d-m-Y H:i') : 'N/A';
        });
        
        $grid->column('to_time', __('To time'))->display(function ($to_time) {
            return $to_time ? \Carbon\Carbon::parse($to_time)->format('d-m-Y H:i') : 'N/A';
        });
        
        $grid->column('return_time', __('Return time'))->display(function ($return_time) {
            return $return_time ? \Carbon\Carbon::parse($return_time)->format('d-m-Y H:i') : 'N/A';
        });
        $grid->column('round_trip', __('Round trip'))->bool();
        $grid->column('km', __('Km'));
        $grid->column('passenger_count', __('Passenger count'));
        $grid->column('total_amount', __('Total amount'));
        $grid->column('payment', __('Payment'));
        $grid->column('trip_status', __('Trip Status'))->display(function ($status) {
            switch ($status) {
                case 'requested':
                    return "<span class='badge bg-info'>Requested</span>";
                case 'accepted':
                    return "<span class='badge bg-primary'>Accepted</span>";
                case 'in_progress':
                    return "<span class='badge bg-warning'>In Progress</span>";
                case 'completed':
                    return "<span class='badge bg-success'>Completed</span>";
                case 'canceled':
                    return "<span class='badge bg-danger'>Canceled</span>";
                default:
                    return $status;
            }
        })->sortable();
        
        $grid->column('trip_type', __('Trip type'));
        $grid->column('cluster_group', __('Cluster group'));
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
        $show->field('scheduled_time', __('Scheduled time'))->as(function ($scheduled_time) {
            return \Carbon\Carbon::parse($scheduled_time)->format('d-m-Y H:i');
        });
        $show->field('from_time', __('From time'))->as(function ($from_time) {
            return \Carbon\Carbon::parse($from_time)->format('d-m-Y H:i');
        });
        $show->field('to_time', __('To time'))->as(function ($to_time) {
            return \Carbon\Carbon::parse($to_time)->format('d-m-Y H:i');
        });
        $show->field('return_time', __('Return time'))->as(function ($return_time) {
            return \Carbon\Carbon::parse($return_time)->format('d-m-Y H:i');
        });
        $show->field('round_trip', __('Round trip'));
        $show->field('km', __('Km'));
        $show->field('passenger_count', __('Passenger count'));
        $show->field('total_amount', __('Total amount'));
        $show->field('payment', __('Payment'));
        $show->field('trip_status', __('Trip status'));
        $show->field('trip_type', __('Trip type'));
        $show->field('cluster_group', __('Cluster group'));
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

        $form->number('driver_id', __('Driver id'))->rules('nullable|integer');
        $form->number('customer_id', __('Customer id'));
        $form->text('from_address', __('From address'));
        $form->decimal('from_lat', __('From lat'))->rules('nullable|numeric');
        $form->decimal('from_lng', __('From lng'))->rules('nullable|numeric');
        $form->text('to_address', __('To address'));
        $form->decimal('to_lat', __('To lat'))->rules('nullable|numeric');
        $form->decimal('to_lng', __('To lng'))->rules('nullable|numeric');
        $form->datetime('scheduled_time', __('Scheduled time'))->format('YYYY-MM-DD HH:mm:ss');
        $form->datetime('from_time', __('From time'))->format('YYYY-MM-DD HH:mm:ss');
        $form->datetime('to_time', __('To time'))->format('YYYY-MM-DD HH:mm:ss');
        $form->datetime('return_time', __('Return time'))->format('YYYY-MM-DD HH:mm:ss');
        $form->switch('round_trip', __('Round trip'))->default(false);
        $form->number('km', __('Km'));
        $form->number('passenger_count', __('Passenger count'))->default(1);
        $form->decimal('total_amount', __('Total amount'))->rules('nullable|numeric');
        $form->text('payment', __('Payment'));
        $form->select('trip_status', __('Trip status'))->options([
            'requested' => 'Requested',
            'accepted' => 'Accepted',
            'in_progress' => 'In Progress',
            'completed' => 'Completed',
            'canceled' => 'Canceled'
        ]);
        $form->text('trip_type', __('Trip type'));
        $form->number('cluster_group', __('Cluster group'))->rules('nullable|numeric');

        return $form;
    }

}

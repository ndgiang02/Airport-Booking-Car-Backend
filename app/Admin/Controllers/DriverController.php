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
        // $grid->model()->where('available', true);

        $totalDrivers = Driver::count();

        $activeDrivers = Driver::where('available', true)->count();

        $grid->header(function ($query) use ($totalDrivers, $activeDrivers) {
            return "
                 <div style='padding: 10px; background-color: #f9f9f9; border-radius: 8px; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);'>
            <div style='display: flex; flex-direction: column; align-items: start;'>
                <div style='font-size: 16px; color: #333;'>
                    <strong>Tổng số tài xế:</strong> $totalDrivers
                </div>
                <div style='font-size: 16px; color: #333; margin-top: 5px;'>
                    <strong>Số tài xế đang hoạt động:</strong> 
                    <span style='color: green;'>$activeDrivers</span>
                </div>
            </div>
        </div>
            ";
        });


        $grid->column('id', __('Id'));
        $grid->column('user_id', __('User id'));
        $grid->column('user.name', __('Name'));
        $grid->column('license_no', __('License no'));
        $grid->column('available', __('Available'))->display(function ($available) {
            $color = $available ? 'green' : 'red';
            $text = $available ? 'Available' : 'Not Available';

            return "<span style='display: inline-block; padding: 5px 10px; color: white; background-color: $color; border-radius: 5px;'>$text</span>";
        });
        $grid->column('latitude', __('Latitude'));
        $grid->column('longitude', __('Longitude'));
        $grid->column('wallet_balance', __('Wallet balance'))->display(function ($wallet_balance) {
            return number_format($wallet_balance, 2);
        });
        $grid->column('device_token', __('Device token'))->limit(20);
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
        $show->field('available', __('Available'))->as(function ($available) {
            return $available ? 'Yes' : 'No';
        });
        $show->field('latitude', __('Latitude'));
        $show->field('longitude', __('Longitude'));
       
        $show->field('wallet_balance', __('Wallet balance'))->as(function ($wallet_balance) {
            return number_format($wallet_balance, 2);
        });
        $show->field('device_token', __('Device token'));
        $show->field('created_at', __('Created at'))->format('d-m-Y H:i');
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
        $form->switch('available', __('Available'))->default(false);
        $form->decimal('latitude', __('Latitude'))->rules('nullable|numeric|min:-90|max:90');
        $form->decimal('longitude', __('Longitude'))->rules('nullable|numeric|min:-180|max:180');
        $form->decimal('wallet_balance', __('Wallet balance'))->default(0.00)->rules('numeric|min:0');
        $form->text('device_token', __('Device token'))->rules('nullable|string|max:255');

        return $form;
    }
}

<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\DriverResource;
use App\Models\Driver;
use App\Models\TripBooking;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use DB;
class DriverController extends Controller
{

	protected $limit;

	public function __construct()
	{
		$this->limit = 20;
	}
	/**
	 * Display a listing of the resource.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function index()
	{
		$users = Driver::paginate($this->limit);
		return response()->json($users);
	}

	public function getData(Request $request)
	{
		$lat1 = $request->get('lat1');
		$lng1 = $request->get('lng1');
		if ($request->get('type_vehicle')) {

			$id_cat_taxi = $request->get('type_vehicle');

			$sql = DB::table('tj_type_vehicule')
				->crossJoin('tj_vehicule')
				->crossJoin('tj_conducteur')
				->leftjoin('brands', 'tj_vehicule.brand', '=', 'brands.id')
				->leftjoin('car_model', 'tj_vehicule.model', '=', 'car_model.id')
				->select('tj_conducteur.id', 'tj_conducteur.nom', 'tj_conducteur.prenom', 'tj_conducteur.phone', 'tj_conducteur.email', 'tj_conducteur.online', 'tj_conducteur.photo_path as photo', 'tj_conducteur.latitude', 'tj_conducteur.longitude', 'tj_vehicule.id as idVehicule', 'brands.name as brand', 'car_model.name as model', 'tj_vehicule.color', 'tj_vehicule.numberplate', 'tj_vehicule.passenger', 'tj_type_vehicule.libelle as typeVehicule')
				->where('tj_vehicule.id_type_vehicule', '=', DB::raw('tj_type_vehicule.id'))
				->where('tj_vehicule.id_conducteur', '=', DB::raw('tj_conducteur.id'))
				->where('tj_vehicule.statut', '=', 'yes')
				->where('tj_conducteur.statut', '=', 'yes')
				->where('tj_conducteur.is_verified', '=', '1')
				->where('tj_conducteur.online', '!=', 'no')
				->where('tj_conducteur.latitude', '!=', '')
				->where('tj_conducteur.longitude', '!=', '')
				->where('tj_type_vehicule.id', '=', DB::raw($id_cat_taxi))->get();

		} else {

			$sql = DB::table('tj_type_vehicule')->crossJoin('tj_vehicule')->crossJoin('tj_conducteur')->select('tj_conducteur.id', 'tj_conducteur.nom', 'tj_conducteur.prenom', 'tj_conducteur.phone', 'tj_conducteur.email', 'tj_conducteur.online', 'tj_conducteur.photo_path as photo', 'tj_conducteur.latitude', 'tj_conducteur.longitude', 'tj_vehicule.id as idVehicule', 'tj_vehicule.brand', 'tj_vehicule.model', 'tj_vehicule.color', 'tj_vehicule.numberplate', 'tj_vehicule.passenger', 'tj_type_vehicule.libelle as typeVehicule')->where('tj_vehicule.id_type_vehicule', '=', DB::raw('tj_type_vehicule.id'))->where('tj_vehicule.id_conducteur', '=', DB::raw('tj_conducteur.id'))->where('tj_vehicule.statut', '=', 'yes')->where('tj_conducteur.statut', '=', 'yes')->where('tj_conducteur.is_verified', '=', '1')->where('tj_conducteur.online', '!=', 'no')->where('tj_conducteur.latitude', '!=', '')->where('tj_conducteur.longitude', '!=', '')->get();
		}

		if ($sql->count() > 0) {

			foreach ($sql as $row) {

				$id_conducteur = $row->id;

				if ($row->latitude != '' && $row->longitude != '')

					$row->distance = DriverController::distance($row->latitude, $row->longitude, $lat1, $lng1, 'K');

				$sql_nb_avis = DB::table('tj_note')->select(DB::raw("COUNT(id) as nb_avis"), DB::raw("SUM(niveau) as somme"))->where('id_conducteur', '=', DB::raw($id_conducteur))->get();

				if (!empty($sql_nb_avis)) {

					foreach ($sql_nb_avis as $row_nb_avis) {

						$somme = $row_nb_avis->somme;
						$nb_avis = $row_nb_avis->nb_avis;

						if ($nb_avis != 0) {
							$moyenne = $somme / $nb_avis;
						} else {
							$moyenne = 0;
						}
					}
				} else {
					$somme = 0;
					$nb_avis = 0;
					$moyenne = 0;
				}

				$row->moyenne = $moyenne;

				$sql_total = DB::table('tj_requete')->select(DB::raw("COUNT(id) as total_completed_ride"))->where('id_conducteur', '=', DB::raw($id_conducteur))->where('statut', '=', 'completed')->get();

				foreach ($sql_total as $row_total) {
					$row->total_completed_ride = $row_total->total_completed_ride;
				}

				$output[] = $row;

			}

			function cmp($a, $b)
			{
				if ($a->distance == $b->distance)
					return 0;
				return ($a->distance < $b->distance) ? -1 : 1;
			}

			usort($output, 'App\Http\Controllers\API\v1\cmp');

			$response['success'] = 'Success';
			$response['error'] = null;
			$response['message'] = 'Successfully fetched data';
			$response['data'] = $output;
		} else {
			$response['success'] = 'Failed';
			$response['error'] = 'Not Found';
		}

		return response()->json($response);

	}

	public function completeTrip($tripId)
	{
		$trip = TripBooking::find($tripId);

		if (!$trip) {
			return response()->json([
				'message' => 'Trip not found'
			], 404);
		}

		if ($trip->status !== 'in_progress') {
			return response()->json([
				'message' => 'Trip cannot be completed'
			], 400);
		}

		$trip->update([
			'status' => 'completed',
			'to_date_time' => now()
		]);

		$driver = $trip->driver;

		if (!$driver) {
			return response()->json([
				'message' => 'Driver not found'
			], 404);
		}

		$totalAmount = $trip->total_amount;
		$driverIncome = $totalAmount * 0.80;
		$platformFee = $totalAmount * 0.20;

		$driver->income += $driverIncome;

		$driver->wallet_balance -= $platformFee;

		$driver->save();

		return response()->json([
			'message' => 'Trip completed successfully and driver income/wallet updated',
			'data' => [
				'trip_id' => $trip->id,
				'driver_id' => $driver->id,
				'total_amount' => $totalAmount,
				'driver_income' => $driverIncome,
				'platform_fee' => $platformFee,
				'new_wallet_balance' => $driver->wallet_balance,
				'new_income' => $driver->income
			]
		], 200);
	}


	/*
	*Show Driver Daily
	*/
	public function showDriverDailyIncome($driverId)
	{
		$today = now()->toDateString();

		$dailyIncome = TripBooking::where('driver_id', $driverId)
			->whereDate('to_date_time', $today)
			->where('status', 'completed')
			->sum(DB::raw('total_amount * 0.80'));

		return response()->json([
			'daily_income' => $dailyIncome
		], 200);
	}

	public function showDriverMonthlyIncome($driverId, $month, $year)
	{
		$monthlyIncome = TripBooking::where('driver_id', $driverId)
			->whereYear('to_date_time', $year)
			->whereMonth('to_date_time', $month)
			->where('status', 'completed')
			->sum(DB::raw('total_amount * 0.80'));

		return response()->json([
			'monthly_income' => $monthlyIncome
		], 200);
	}

	public function updateDriverLocation(Request $request)
    {
        $validatedData = $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        $driver = auth()->user()->driver;

        if (!$driver) {
            return response()->json([
                'message' => 'Driver not found'
            ], 404);
        }

        $driver->latitude = $validatedData['latitude'];
        $driver->longitude = $validatedData['longitude'];
        $driver->save();

        return response()->json([
            'message' => 'Driver location updated successfully',
            'data' => [
                'driver_id' => $driver->id,
                'latitude' => $driver->latitude,
                'longitude' => $driver->longitude,
            ]
        ], 200);
    }

    

}

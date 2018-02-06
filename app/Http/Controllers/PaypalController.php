<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Srmklive\PayPal\Services\ExpressCheckout;
use App\Invoice;



	class PaypalController extends Controller
		{
		    //
		    protected $provider;
			public function __construct() {
			    $this->provider = new ExpressCheckout();
			}

		    public function expressCheckout(Request $request) {

				  
				  $invoice_id = Invoice::count() + 1;
				    
				  $cart = $this->getCart($invoice_id);

				  
				  $invoice = new Invoice();
				  $invoice->title = $cart['invoice_description'];
				  $invoice->price = $cart['total'];
				  $invoice->save();

				  $response = $this->provider->setExpressCheckout($cart);

				  
				  if (!$response['paypal_link']) {
				    return redirect('/')->with(['code' => 'danger', 'message' => $response['L_LONGMESSAGE0']]);
				  }

				
				  return redirect($response['paypal_link']);
			}	



			private function getCart($invoice_id){


			        return [
			            'items' => [
			                [
			                    'name' => 'FileToDownload',
			                    'price' => 20,
			                    'qty' => 1,
			                ],
			               
			            ],
			            'return_url' => url('/paypal/express-checkout-success'),
			            'invoice_id' => config('paypal.invoice_prefix') . '_' . $invoice_id,
			            'invoice_description' => "Order #" . $invoice_id . " Invoice",
			            'cancel_url' => url('/'),
			            'total' => 20,
			        ];
    				}


    		public function expressCheckoutSuccess(Request $request) {


					        $token = $request->get('token');

					        $PayerID = $request->get('PayerID');

					        $response = $this->provider->getExpressCheckoutDetails($token);

					       
					        if (!in_array(strtoupper($response['ACK']), ['SUCCESS', 'SUCCESSWITHWARNING'])) {
					            return redirect('/')->with(['code' => 'danger', 'message' => 'Error processing PayPal payment']);
					        }

					        
					        $invoice_id = explode('_', $response['INVNUM'])[1];

					        
					        $cart = $this->getCart($invoice_id);

					            $payment_status = $this->provider->doExpressCheckoutPayment($cart, $token, $PayerID);
					            $status = $payment_status['PAYMENTINFO_0_PAYMENTSTATUS'];

					        $invoice = Invoice::find($invoice_id);

					        $invoice->payment_status = $status;
					        $invoice->save();
					        if ($invoice->paid) {
					            return redirect()->route('downloadFile');
					        }
					        
					        return redirect('/home')->with(['code' => 'danger', 'message' => 'Error processing PayPal payment for Order ' . $invoice->id . '!']);
					    }
	}

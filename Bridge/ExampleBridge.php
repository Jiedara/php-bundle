<?php

App::uses('Bridge', 'Bridges');

/**
 * ProductBridge class permit to process action
 * from AdminQusto to InvoiceQusto,
 * like creating Invoice when
 * new product are ordered
 */
class ProductBridge extends Bridge
{
	protected $from = 'qusto_product_items';
	protected $to = 'invoice_products';

	protected $fromKey = 'product_id_admin';
	protected $toKey = 'product_id_invoice';

	/**
	 * Add the wanted product to the Invoice Client view in qusto invoice
	 *@param  array $pos point of sale related to the invoice (with billing account info inside )
	 *@param  array $product list of product (issued from the qusto_product_items table)
	 *@return void
	 */
	public function productToInvoice($pos, $product){

		$invoiceProducts = $this->cross($product['id']);

		foreach($invoiceProducts as $invoiceProduct ){
			$invoiceProduct = $invoiceProduct['invoice_products'];
			$this->query('
				INSERT INTO invoice_sold_products (`product_id`, `client_id`, `label`, `quantity`, `price`, `vat`, `created`, `point_of_sale_id`, `comment`)
				VALUES (
					' . $invoiceProduct['id'] . ',
					' . $pos['BillingAccount']['invoice_client_id'] . ',
					\'' . $invoiceProduct['label'] . '\',
					' . $product['productItemsPurchaseOrders']['quantity'] . ',
					' . $invoiceProduct['price'] . ',
					' . $invoiceProduct['vat'] . ',
					"' . date('Y-m-d H:i:s') . '",
					' . $pos['id'] . ',
					"Automatic generation from ProductBridge in Admin"
				)'
			);
		}
	}
}

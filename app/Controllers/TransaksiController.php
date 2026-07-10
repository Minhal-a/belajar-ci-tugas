<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;
use App\Services\RajaOngkirService;

use App\Models\TransactionModel;
use App\Models\TransactionDetailModel;
use App\Models\DiscountModel;

class TransaksiController extends BaseController
{
    protected $cart;
    protected $transactionModel;
    protected $transactionDetailModel;
    protected $discountModel;

    public function __construct()
    {
        helper(['number', 'form']);
        $this->cart = service('cart');
        $this->transactionModel = new TransactionModel();
        $this->transactionDetailModel = new TransactionDetailModel(); 
        $this->discountModel = new DiscountModel();
    }

    public function index()
    {  
        $diskonHariIni = $this->discountModel->getDiskonHariIni();
        $nominalDiskon = $diskonHariIni ? $diskonHariIni['nominal'] : 0;

        $items = $this->cart->contents();
        $total = 0;

        foreach ($items as &$item) {
            $hargaSetelahDiskon = max(0, $item['price'] - $nominalDiskon);
            $item['harga_setelah_diskon'] = $hargaSetelahDiskon;
            $item['subtotal_setelah_diskon'] = $hargaSetelahDiskon * $item['qty'];
            $total += $item['subtotal_setelah_diskon'];
        }
        unset($item);

        $data = [
            'items'         => $items,
            'total'         => $total,
            'diskonHariIni' => $diskonHariIni
        ];

        return view('v_keranjang', $data);
    }

    public function cart_add()
    {
        $this->cart->insert([
            'id'      => $this->request->getPost('id'),
            'qty'     => 1,
            'price'   => $this->request->getPost('harga'),
            'name'    => $this->request->getPost('nama'),
            'options' => [
                'foto' => $this->request->getPost('foto')
            ]
        ]);
        
        session()->setFlashdata(
            'success',
            'Produk berhasil ditambahkan ke keranjang. 
            <a href="' . base_url('keranjang') . '">Lihat</a>'
        );
        
        return redirect()->to(base_url('/'));
    } 

    public function cart_edit()
    {
        $i = 1;
        foreach ($this->cart->contents() as $item) {
            $qty = $this->request->getPost('qty' . $i++);

            $this->cart->update([
                'rowid' => $item['rowid'],
                'qty'   => $qty
            ]);
        }

        session()->setFlashdata(
            'success',
            'Keranjang berhasil diperbarui'
        );

        return redirect()->to(base_url('keranjang'));
    }

    public function cart_delete($rowid)
    {
        $this->cart->remove($rowid);

        session()->setFlashdata(
            'success',
            'Produk berhasil dihapus dari keranjang'
        );

        return redirect()->to(base_url('keranjang'));
    }

    public function cart_clear()
    {
        $this->cart->destroy();

        session()->setFlashdata(
            'success',
            'Keranjang berhasil dikosongkan'
        );

        return redirect()->to(base_url('keranjang'));
    }

    public function checkout()
    {  
        $diskonHariIni = $this->discountModel->getDiskonHariIni();
        $nominalDiskon = $diskonHariIni ? $diskonHariIni['nominal'] : 0;

        $items = $this->cart->contents();
        $total = 0;

        foreach ($items as &$item) {
            $hargaSetelahDiskon = max(0, $item['price'] - $nominalDiskon);
            $item['harga_setelah_diskon'] = $hargaSetelahDiskon;
            $item['subtotal_setelah_diskon'] = $hargaSetelahDiskon * $item['qty'];
            $total += $item['subtotal_setelah_diskon'];
        }
        unset($item);

        $data = [
            'items'         => $items,
            'total'         => $total,
            'diskonHariIni' => $diskonHariIni
        ];

        return view('v_checkout', $data);
    }

    public function destinations()
    {
        $search = $this->request->getGet('q'); 

        if (empty($search)) {
            return $this->response->setJSON([
            'results'=> []
            ]);
        }

        $service = new RajaOngkirService();
        $response = $service->getDestination($search);

        $results = [];
        $data = $response['data'] ?? [];

        foreach ($data as $item) {
            $results[] = [
                'id'   => $item['id'],
                'text' => $item['label']
            ];
        }

        return $this->response->setJSON([
            'results' => $results
        ]);
    }

    public function costs()
    {
        $origin = '64999';
        $destination = $this->request->getGet('destination');
        $weight = '1000';
        $courier = 'jne'; 

        $service = new RajaOngkirService();
        $response = $service->getCost($origin, $destination, $weight, $courier);

        $results = [];
        $data = $response['data'] ?? [];

        foreach ($data as $item) {
            $results[] = [
                'service'     => $item['service'],
                'description' => $item['description'],
                'cost'        => $item['cost'],
                'etd'         => $item['etd']
            ];
        }

        return $this->response->setJSON($results);
    }

    public function buy()
    { 
        $cartItems = $this->cart->contents();

        if (empty($cartItems)) {
            return redirect()->back();
        }

        $diskonHariIni = $this->discountModel->getDiskonHariIni();
        $nominalDiskon = $diskonHariIni ? $diskonHariIni['nominal'] : 0;

        $db = \Config\Database::connect();
        $db->transStart(); 

        $subtotal = 0;
        foreach ($cartItems as $item) {
            $hargaSetelahDiskon = max(0, $item['price'] - $nominalDiskon);
            $subtotal += $item['qty'] * $hargaSetelahDiskon;
        }

        $ongkir = (int) $this->request->getPost('ongkir');

        $transaction = [
            'username'    => $this->request->getPost('username'),
            'alamat'      => $this->request->getPost('alamat'),
            'ongkir'      => $ongkir,
            'total_harga' => $subtotal + $ongkir,
            'status'      => 0, 
        ];

        // insert transaction
        if (!$this->transactionModel->insert($transaction)) {
            $db->transRollback();
            return redirect()->back()->with('error', 'Gagal membuat transaksi');
        }

        $transactionId = $this->transactionModel->getInsertID();

        // insert transaction detail
        foreach ($cartItems as $item) {
            $hargaSetelahDiskon = max(0, $item['price'] - $nominalDiskon);

            $this->transactionDetailModel->insert([
                'transaction_id' => $transactionId,
                'product_id'     => $item['id'],
                'jumlah'         => $item['qty'],
                'diskon'         => $nominalDiskon,
                'subtotal_harga' => $item['qty'] * $hargaSetelahDiskon
            ]);
        }

        $db->transComplete();

        if (!$db->transStatus()) {
            return redirect()->back()->with('error', 'Gagal membuat transaksi');
        }

        // hapus session keranjang belanja 
        $this->cart->destroy();
        return redirect()->to(base_url());
    }

    public function history()
    {
        $username = session()->get('username'); 
    
        $transactions = $this->transactionModel->where('username', $username)->findAll();
        $transactionIds = array_column($transactions, 'id');

        $products = $this->transactionDetailModel->getProductsByTransactionIds($transactionIds);

        $data = [
            'username'      => $username,
            'transactions'  => $transactions,
            'products'      => $products
        ]; 

        return view('v_history', $data);
    }
}
<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\TransactionModel;
use App\Models\TransactionDetailModel;

class PembelianController extends BaseController
{
    protected $transactionModel;
    protected $transactionDetailModel;

    function __construct()
    {
        helper(['number', 'form']);
        $this->transactionModel = new TransactionModel();
        $this->transactionDetailModel = new TransactionDetailModel();
    }

    public function index()
    {
        $transactions = $this->transactionModel->orderBy('created_at', 'DESC')->findAll();
        $transactionIds = array_column($transactions, 'id');

        $products = $this->transactionDetailModel->getProductsByTransactionIds($transactionIds);

        return view('pembelian/index', [
            'transactions' => $transactions,
            'products'     => $products
        ]);
    }

    public function updateStatus($id)
    {
        $status = $this->request->getPost('status');

        $this->transactionModel->update($id, [
            'status' => $status
        ]);

        return redirect()->to('pembelian')->with('success', 'Status Pesanan Berhasil Diubah');
    }
}
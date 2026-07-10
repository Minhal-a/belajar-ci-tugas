<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\DiscountModel;

class DiskonController extends BaseController
{
    protected $discountModel;

    function __construct()
    {
        helper(['form', 'url']);
        $this->discountModel = new DiscountModel();
    }

    public function index()
    {
        return view('diskon/index', [
            'discounts' => $this->discountModel->orderBy('tanggal', 'ASC')->findAll()
        ]);
    }

    public function create()
{
    $rules = [
        'tanggal' => 'required|valid_date',
        'nominal' => 'required|numeric',
    ];

    if (!$this->validate($rules)) {
        session()->setFlashdata('failed', $this->validator->listErrors());
        return redirect()->to('diskon')->withInput();
    }

    $tanggal = $this->request->getPost('tanggal');

    $existing = $this->discountModel->where('tanggal', $tanggal)->first();

    if ($existing) {
        session()->setFlashdata('failed', 'The tanggal field must contain a unique value.');
        return redirect()->to('diskon')->withInput();
    }

    $this->discountModel->insert([
        'tanggal' => $tanggal,
        'nominal' => $this->request->getPost('nominal'),
    ]);

    return redirect()->to('diskon')->with('success', 'Data Diskon Berhasil Ditambah');
}

    public function edit($id)
    {
        $rules = [
            'nominal' => 'required|numeric',
        ];

        if (!$this->validate($rules)) {
            session()->setFlashdata('failed', $this->validator->listErrors());
            return redirect()->to('diskon');
        }

        $this->discountModel->update($id, [
            'nominal' => $this->request->getPost('nominal'),
        ]);

        return redirect()->to('diskon')->with('success', 'Data Diskon Berhasil Diubah');
    }

    public function delete($id)
    {
        $this->discountModel->delete($id);

        return redirect()->to('diskon')->with('success', 'Data Diskon Berhasil Dihapus');
    }
}
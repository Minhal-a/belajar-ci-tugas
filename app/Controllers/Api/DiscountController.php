<?php

namespace App\Controllers\Api;

use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

use App\Models\DiscountModel;

class DiscountController extends ResourceController
{
    protected $model;
    private $token;

    function __construct()
    {
        $this->model = new DiscountModel();
        $this->token = env('MY_API_KEY');
    }

    private function authenticate()
    {
        $header = $this->request->getHeaderLine('Authorization');

        if (empty($header)) {
            return false;
        }

        if (!preg_match('/Bearer\s+(.*)$/i', $header, $matches)) {
            return false;
        }

        return $matches[1] === $this->token;
    }

    private function unauthorized()
    {
        return $this->respond([
            'status'  => false,
            'message' => 'Unauthorized'
        ], 401);
    }

    /**
     * GET /api/discounts
     */
    public function index()
    {
        if (!$this->authenticate()) {
            return $this->unauthorized();
        }

        $page = (int) ($this->request->getGet('page') ?? 1);
        $perPage = (int) ($this->request->getGet('per_page') ?? 10);

        $discounts = $this->model->paginate($perPage, 'default', $page);

        return $this->respond([
            'data' => $discounts,
            'pagination' => [
                'current_page' => $page,
                'per_page'     => $perPage,
                'last_page'    => $this->model->pager->getPageCount(),
                'total_data'   => $this->model->pager->getTotal(),
                'has_next'     => $page < $this->model->pager->getPageCount(),
                'has_prev'     => $page > 1,
            ]
        ]);
    }

    /**
     * GET /api/discounts/{id}
     */
    public function show($id = null)
    {
        if (!$this->authenticate()) {
            return $this->unauthorized();
        }

        $discount = $this->model->find($id);

        if (!$discount) {
            return $this->failNotFound('Data diskon tidak ditemukan');
        }

        return $this->respond($discount);
    }

    public function new()
    {
        //
    }

    /**
     * POST /api/discounts
     */
    public function create()
    {
        if (!$this->authenticate()) {
            return $this->unauthorized();
        }

        $data = $this->request->getJSON(true);

        if (empty($data['tanggal']) || !isset($data['nominal'])) {
            return $this->fail('Field tanggal dan nominal wajib diisi', 400);
        }

        // cek unik manual lewat Model, biar data yang sudah di-soft-delete diabaikan
        $existing = $this->model->where('tanggal', $data['tanggal'])->first();

        if ($existing) {
            return $this->fail('Sudah ada data diskon untuk tanggal tersebut', 400);
        }

        $this->model->insert($data);

        return $this->respondCreated([
            'message' => 'Data diskon berhasil ditambahkan'
        ]);
    }

    public function edit($id = null)
    {
        //
    }

    /**
     * PUT/PATCH /api/discounts/{id}
     */
    public function update($id = null)
    {
        if (!$this->authenticate()) {
            return $this->unauthorized();
        }

        if (!$this->model->find($id)) {
            return $this->failNotFound('Data diskon tidak ditemukan');
        }

        $data = $this->request->getJSON(true);

        // tanggal sengaja tidak boleh diubah lewat endpoint ini
        unset($data['tanggal']);

        $this->model->update($id, $data);

        return $this->respond([
            'message' => 'Data diskon berhasil diperbarui'
        ]);
    }

    /**
     * DELETE /api/discounts/{id}
     */
    public function delete($id = null)
    {
        if (!$this->authenticate()) {
            return $this->unauthorized();
        }

        if (!$this->model->find($id)) {
            return $this->failNotFound('Data diskon tidak ditemukan');
        }

        $this->model->delete($id);

        return $this->respondDeleted([
            'message' => 'Data diskon berhasil dihapus'
        ]);
    }
}
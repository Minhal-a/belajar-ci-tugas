<?php

namespace App\Controllers;

use App\Models\ProductModel;
use App\Models\DiscountModel;

class Home extends BaseController
{
    protected $productModel;
    protected $discountModel;

    function __construct(){
        helper(['number', 'form']);
        $this->productModel = new ProductModel();
        $this->discountModel = new DiscountModel();
    }

    public function index(): string
    {
        $products = $this->productModel->findAll();
        $diskonHariIni = $this->discountModel->getDiskonHariIni();

        $data['products'] = $products;
        $data['diskonHariIni'] = $diskonHariIni;

        return view('v_home', $data);
    }

    public function profile(): string
    {
        return view('v_profile');
    }
}
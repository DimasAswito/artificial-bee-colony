<?php

namespace App\View\Components\ecommerce;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class EcommerceMetrics extends Component
{
    public $totalDosen;
    public $totalMataKuliah;
    public $totalRuangan;
    public $totalRiwayat;

    public function __construct($totalDosen = 0, $totalMataKuliah = 0, $totalRuangan = 0, $totalRiwayat = 0)
    {
        $this->totalDosen = $totalDosen;
        $this->totalMataKuliah = $totalMataKuliah;
        $this->totalRuangan = $totalRuangan;
        $this->totalRiwayat = $totalRiwayat;
    }

    public function render(): View|Closure|string
    {
        return view('components.ecommerce.ecommerce-metrics');
    }
}

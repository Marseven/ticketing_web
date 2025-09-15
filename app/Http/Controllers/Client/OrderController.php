<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of user orders.
     */
    public function index(Request $request)
    {
        $query = Auth::user()->orders()
            ->with(['event', 'schedule', 'orderItems.ticketType', 'payment']);

        // Filtrage par statut
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $orders = $query->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();

        $statuses = Order::select('status')
            ->distinct()
            ->pluck('status')
            ->toArray();

        return view('client.orders.index', compact('orders', 'statuses'));
    }

    /**
     * Display the specified order.
     */
    public function show(Order $order)
    {
        // Vérifier que l'utilisateur peut voir cette commande
        if ($order->buyer_id !== Auth::id()) {
            abort(403, 'Accès refusé');
        }

        $order->load([
            'event',
            'schedule',
            'orderItems.ticketType',
            'payment',
            'tickets' => function ($query) {
                $query->with('checkins');
            }
        ]);

        return view('client.orders.show', compact('order'));
    }
}
@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="p-6 bg-gray-100 border-b">
            <h1 class="text-2xl font-bold">{{ $product->brand_name ?? 'Nuk ka emër' }}</h1>
            <p class="text-gray-600">{{ $product->ndc_code }}</p>
        </div>

        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h2 class="text-lg font-semibold mb-2">Informacion bazë</h2>
                    <ul class="space-y-2">
                        <li><span class="font-medium">Emri gjenerik:</span> {{ $product->generic_name ?? '-' }}</li>
                        <li><span class="font-medium">Prodhuesi:</span> {{ $product->labeler_name ?? '-' }}</li>
                        <li><span class="font-medium">Lloji:</span> {{ $product->product_type ?? '-' }}</li>
                    </ul>
                </div>

                <div>
                    <h2 class="text-lg font-semibold mb-2">Veprime</h2>
                    <div class="flex space-x-3">
                        <a href="{{ route('products.index') }}" 
                           class="bg-gray-200 text-gray-800 px-4 py-2 rounded hover:bg-gray-300">
                            Kthehu te lista
                        </a>
                        @auth
                        <form action="{{ route('products.destroy', $product) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <button type="submit" 
                                    class="bg-red-100 text-red-800 px-4 py-2 rounded hover:bg-red-200"
                                    onclick="return confirm('Jeni i sigurt?')">
                                Fshi produktin
                            </button>
                        </form>
                        @endauth
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
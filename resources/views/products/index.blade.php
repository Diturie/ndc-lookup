@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold mb-6">Të gjitha produktet</h1>
    
    <div class="bg-white shadow-md rounded-lg overflow-hidden">
        <table class="min-w-full">
            <thead class="bg-gray-100">
                <tr>
                    <th class="py-3 px-4 text-left">Kodi NDC</th>
                    <th class="py-3 px-4 text-left">Emri</th>
                    <th class="py-3 px-4 text-left">Prodhuesi</th>
                    <th class="py-3 px-4 text-left">Lloji</th>
                </tr>
            </thead>
            <tbody>
                @foreach($products as $product)
                <tr class="border-b border-gray-200 hover:bg-gray-50">
                    <td class="py-3 px-4">{{ $product->ndc_code }}</td>
                    <td class="py-3 px-4">{{ $product->brand_name }}</td>
                    <td class="py-3 px-4">{{ $product->labeler_name }}</td>
                    <td class="py-3 px-4">{{ $product->product_type }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $products->links() }}
    </div>
</div>
@endsection
@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold mb-6">Rezultatet e Kërkimit</h1>

    <div class="bg-white shadow-lg rounded-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">KODI NDC</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">EMRI PRODUKTIT</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">PRODHUESI</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">LLOJI I PRODUKTIT</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">BURIMI</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach($results as $result)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap">{{ $result['ndc_code'] }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">{{ $result['brand_name'] }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">{{ $result['labeler_name'] }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">{{ $result['product_type'] }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">{{ $result['source'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-6">
        <a href="{{ route('products.search.form') }}" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded">
            Kërkim i Ri
        </a>
    </div>
</div>
@endsection 
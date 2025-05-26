<div class="py-6 sm:py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-4 sm:p-6">
            <!-- Logo and Title -->
            <div class="flex flex-col items-center justify-center mb-6 sm:mb-8 space-y-4">
                <!-- Logo -->
                <div class="flex-shrink-0">
                    <svg class="h-10 w-10 sm:h-12 sm:w-12 text-indigo-600" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M19.5 5.25H4.5C4.08579 5.25 3.75 5.58579 3.75 6V18C3.75 18.4142 4.08579 18.75 4.5 18.75H19.5C19.9142 18.75 20.25 18.4142 20.25 18V6C20.25 5.58579 19.9142 5.25 19.5 5.25Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M8.625 9.75H15.375" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M8.625 14.25H15.375" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M12 7.5V16.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <!-- Title -->
                <div class="text-center">
                    <h1 class="text-3xl sm:text-4xl font-extrabold text-gray-900 tracking-tight">
                        NDC <span class="text-indigo-600">LookUp</span>
                    </h1>
                    <p class="mt-1 text-sm sm:text-base text-gray-500">Search and manage National Drug Codes</p>
                </div>
            </div>

            <!-- Search Box -->
            <div class="mb-6 sm:mb-8">
                <div class="flex flex-col sm:flex-row gap-4 items-center">
                    <div class="w-full">
                        <input type="text" 
                            wire:model="searchTerm" 
                            wire:keydown.enter="search"
                            placeholder="Enter NDC code (e.g., 0002-1433)" 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    </div>
                    <button wire:click="search" 
                        class="w-full sm:w-auto px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 transition-colors duration-200"
                        wire:loading.attr="disabled">
                        Search
                    </button>
                </div>

                <!-- Simple Loading Indicator -->
                <div wire:loading class="flex items-center justify-center w-full mt-4 space-x-3">
                    <div class="flex items-center space-x-3">
                        <svg class="animate-spin h-5 w-5 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span class="text-indigo-600">Searching...</span>
                    </div>
                </div>
            </div>

            @if(!empty($debug))
                <div class="mb-4 p-4 bg-gray-100 rounded-lg">
                    <p class="text-sm text-gray-600">Debug: {{ $debug }}</p>
                </div>
            @endif

            <!-- Results Section -->
            <div class="space-y-6">
                <!-- Export and Clear Buttons Section -->
                @if(!$results->isEmpty())
                    <div class="bg-gray-50 p-4 rounded-lg shadow-sm">
                        <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
                            <button wire:click="clearResults" 
                                class="w-full sm:w-auto bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded-lg shadow-sm transition-colors duration-200">
                                Clear Results
                            </button>
                            <button wire:click="exportToCsv" 
                                class="w-full sm:w-auto bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-lg shadow-sm transition-colors duration-200 flex items-center justify-center space-x-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                                <span>Export to CSV</span>
                            </button>
                        </div>
                    </div>
                @endif

                <!-- Results Table -->
                @if(!$results->isEmpty())
                    <div class="overflow-x-auto">
                        <div class="inline-block min-w-full align-middle">
                            <div class="overflow-hidden border border-gray-200 sm:rounded-lg">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">NDC CODE</th>
                                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">BRAND NAME</th>
                                            <th scope="col" class="hidden sm:table-cell px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">GENERIC NAME</th>
                                            <th scope="col" class="hidden md:table-cell px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">LABELER</th>
                                            <th scope="col" class="hidden lg:table-cell px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">PRODUCT TYPE</th>
                                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">SOURCE</th>
                                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ACTIONS</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        @foreach($results as $result)
                                            <tr class="hover:bg-gray-50">
                                                <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                    {{ $result['ndc_code'] }}
                                                </td>
                                                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    {{ $result['brand_name'] }}
                                                </td>
                                                <td class="hidden sm:table-cell px-4 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    {{ $result['generic_name'] }}
                                                </td>
                                                <td class="hidden md:table-cell px-4 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    {{ $result['labeler_name'] }}
                                                </td>
                                                <td class="hidden lg:table-cell px-4 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    {{ $result['product_type'] }}
                                                </td>
                                                <td class="px-4 py-4 whitespace-nowrap text-sm">
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                        @if($result['source'] === 'Database') bg-blue-100 text-blue-800
                                                        @elseif($result['source'] === 'OpenFDA') bg-green-100 text-green-800
                                                        @else bg-red-100 text-red-800
                                                        @endif">
                                                        {{ $result['source'] }}
                                                    </span>
                                                </td>
                                                <td class="px-4 py-4 whitespace-nowrap text-sm">
                                                    <button wire:click="deleteNdc('{{ $result['ndc_code'] }}')"
                                                        class="text-red-600 hover:text-red-900 focus:outline-none transition-colors duration-200"
                                                        onclick="return confirm('Are you sure you want to delete this NDC record?')">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                            <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                                        </svg>
                                                    </button>
                                                </td>
                                            </tr>
                                            <!-- Responsive Row for Mobile -->
                                            <tr class="sm:hidden border-t border-gray-100">
                                                <td colspan="7" class="px-4 py-2 text-sm text-gray-500">
                                                    <div class="space-y-1">
                                                        <div><span class="font-medium">Generic:</span> {{ $result['generic_name'] }}</div>
                                                        <div><span class="font-medium">Labeler:</span> {{ $result['labeler_name'] }}</div>
                                                        <div><span class="font-medium">Type:</span> {{ $result['product_type'] }}</div>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

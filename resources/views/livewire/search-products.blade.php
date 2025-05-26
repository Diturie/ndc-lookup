<!-- resources/views/livewire/search-products.blade.php -->
<div>
    <div class="search-box">
        <h2 class="search-title">Aplikacion për Kërkimin e Llaqeve</h2>
        
        <div class="input-group">
            <input wire:model.debounce.500ms="ndcCodes" type="text" 
                   placeholder="Shkruaj kodet NDC të ndara me presje, p.sh.: 12345-6789, 11111-2222, 99999-0000">
            <button wire:click="search" wire:loading.attr="disabled">
                <span wire:loading.remove>Kërko</span>
                <span wire:loading>
                    <svg class="spinner" width="20" height="20" viewBox="0 0 50 50">
                        <circle cx="25" cy="25" r="20" fill="none" stroke-width="5"></circle>
                    </svg>
                </span>
            </button>
        </div>
    </div>
    
    <!-- ... pjesa tjetër e kodit ... -->
</div>
    @if(count($results) > 0)
        <table class="results-table">
            <thead>
                <tr>
                    <th>KODI NDC</th>
                    <th>EMRI PRODUKTIT</th>
                    <th>PRODHUESI</th>
                    <th>LLOJI I PRODUKTIT</th>
                    <th>BURIMI</th>
                </tr>
            </thead>
            <tbody>
                @foreach($results as $result)
                    <tr>
                        <td>{{ $result['ndc_code'] }}</td>
                        <td>{{ $result['brand_name'] }}</td>
                        <td>{{ $result['labeler_name'] }}</td>
                        <td>{{ $result['product_type'] }}</td>
                        <td class="source-{{ strtolower(str_replace(' ', '', $result['source'])) }}">
                            {{ $result['source'] }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
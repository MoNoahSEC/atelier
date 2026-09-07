@extends('layouts.admin')

@section('title', 'Products Management')

@section('content')
<div class="space-y-6" x-data="productsManager()">

    <!-- Header & Action Bar -->
    <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4 border-b-2 border-black pb-4">
        <div>
            <span class="text-[10px] font-mono font-bold uppercase tracking-widest text-gray-500">CATALOG MANAGEMENT</span>
            <h1 class="text-3xl font-black uppercase tracking-tight text-black">All Products</h1>
            <p class="text-xs text-gray-500 mt-0.5">{{ $products->total() }} total pieces registered in store inventory</p>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <!-- CSV Export -->
            <a href="{{ route('admin.products.export') }}" class="border-2 border-black bg-white px-3.5 py-2 text-xs font-bold uppercase hover:bg-gray-100 flex items-center gap-1.5 shadow-[2px_2px_0px_0px_rgba(0,0,0,1)]">
                <span>📥</span>
                <span>Export CSV</span>
            </a>

            <!-- CSV Import Modal Trigger -->
            <button @click="importModal = true" class="border-2 border-black bg-white px-3.5 py-2 text-xs font-bold uppercase hover:bg-gray-100 flex items-center gap-1.5 shadow-[2px_2px_0px_0px_rgba(0,0,0,1)]">
                <span>📤</span>
                <span>Import CSV</span>
            </button>

            <!-- Add Product -->
            <a href="{{ route('admin.products.create') }}" class="bg-black text-white px-4 py-2 text-xs font-bold uppercase tracking-widest hover:bg-gray-800 shadow-[3px_3px_0px_0px_rgba(0,0,0,1)] transition-transform active:translate-y-0.5 flex items-center gap-1.5">
                <span>+</span>
                <span>Add Product</span>
            </a>
        </div>
    </div>

    <!-- Bulk Actions Floating Bar (Conditional when items selected) -->
    <div 
        x-show="selected.length > 0" 
        x-cloak 
        class="sticky top-16 z-30 bg-black text-white p-3 border-2 border-black shadow-[6px_6px_0px_0px_rgba(0,0,0,0.5)] flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 animate-fadeIn"
    >
        <div class="flex items-center gap-3">
            <span class="bg-white text-black px-2 py-0.5 text-xs font-mono font-bold">
                <span x-text="selected.length"></span> Selected
            </span>
            <span class="text-xs font-bold uppercase tracking-wider text-gray-300">Bulk Actions:</span>
        </div>

        <form action="{{ route('admin.products.bulk') }}" method="POST" class="flex items-center gap-2 flex-wrap w-full sm:w-auto" onsubmit="return confirmBulkAction(this)">
            @csrf
            <!-- Hidden inputs for selected IDs -->
            <template x-for="id in selected" :key="id">
                <input type="hidden" name="product_ids[]" :value="id">
            </template>

            <select name="action" x-model="bulkActionType" class="border border-white bg-black text-white p-1.5 text-xs font-bold uppercase focus:outline-none cursor-pointer">
                <option value="activate">✅ Set Active</option>
                <option value="draft">📝 Set Draft</option>
                <option value="price_percent">📈 Adjust Price by % (+/-)</option>
                <option value="price_fixed">💵 Adjust Price by EGP (+/-)</option>
                <option value="delete">🗑 Delete Selected</option>
            </select>

            <input 
                x-show="bulkActionType === 'price_percent' || bulkActionType === 'price_fixed'"
                type="number" 
                step="0.01" 
                name="adjustment_value" 
                placeholder="e.g. 10 or -50" 
                class="w-28 border border-white p-1.5 text-xs bg-white text-black font-mono font-bold focus:outline-none"
            >

            <button type="submit" class="bg-white text-black hover:bg-gray-200 px-4 py-1.5 text-xs font-bold uppercase tracking-wider shadow-sm transition-colors">
                Apply Action
            </button>
            <button type="button" @click="selected = []" class="text-xs text-gray-400 hover:text-white underline ml-2">
                Clear
            </button>
        </form>
    </div>

    <!-- Search & Filters Toolbar -->
    <div class="bg-white border-2 border-black p-4 shadow-[4px_4px_0px_0px_rgba(0,0,0,1)]">
        <form method="GET" action="{{ route('admin.products.index') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-3">
            
            <!-- Search -->
            <div class="sm:col-span-2">
                <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-600 mb-1">Search Products</label>
                <input 
                    type="text" 
                    name="search" 
                    value="{{ request('search') }}" 
                    placeholder="Search by product name, SKU..." 
                    class="w-full border-2 border-black p-2 text-xs focus:outline-none"
                >
            </div>

            <!-- Status Filter -->
            <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-600 mb-1">Status</label>
                <select name="status" class="w-full border-2 border-black p-2 text-xs bg-white focus:outline-none">
                    <option value="">All Statuses</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>✅ Active</option>
                    <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>📝 Draft</option>
                    <option value="archived" {{ request('status') === 'archived' ? 'selected' : '' }}>📦 Archived</option>
                </select>
            </div>

            <!-- Collection Filter -->
            <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-600 mb-1">Collection</label>
                <div class="flex items-center gap-2">
                    <select name="collection_id" class="w-full border-2 border-black p-2 text-xs bg-white focus:outline-none">
                        <option value="">All Collections</option>
                        @foreach($collections as $col)
                            <option value="{{ $col->id }}" {{ request('collection_id') == $col->id ? 'selected' : '' }}>{{ $col->title }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="bg-black text-white px-4 py-2 text-xs font-bold uppercase hover:bg-gray-800">
                        Filter
                    </button>
                </div>
            </div>

        </form>
    </div>

    <!-- Products Table -->
    <div class="bg-white border-2 border-black shadow-[6px_6px_0px_0px_rgba(0,0,0,1)] overflow-hidden">
        @if($products->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b-2 border-black bg-gray-50 text-[10px] font-bold uppercase tracking-wider text-gray-600">
                            <th class="p-3 w-8 text-center">
                                <input type="checkbox" @change="toggleSelectAll($event)" class="w-4 h-4 accent-black cursor-pointer">
                            </th>
                            <th class="p-3 w-16">Image</th>
                            <th class="p-3">Product Name & SKU</th>
                            <th class="p-3">Collections</th>
                            <th class="p-3">Price</th>
                            <th class="p-3">Stock</th>
                            <th class="p-3">Status</th>
                            <th class="p-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($products as $product)
                            @php
                                $img = $product->image_url ?: 'https://images.unsplash.com/photo-1627123424574-724758594e93?w=300';
                                $imgUrl = str_starts_with($img, 'http') ? $img : url($img);
                            @endphp
                            <tr class="hover:bg-gray-50 transition-colors" :class="selected.includes('{{ $product->id }}') ? 'bg-amber-50/60' : ''">
                                <!-- Checkbox -->
                                <td class="p-3 text-center">
                                    <input type="checkbox" value="{{ $product->id }}" x-model="selected" class="w-4 h-4 accent-black cursor-pointer">
                                </td>

                                <!-- Thumbnail -->
                                <td class="p-3">
                                    <div class="w-12 h-12 border border-black bg-gray-100 overflow-hidden relative group">
                                        <img src="{{ $imgUrl }}" alt="{{ $product->title }}" class="w-full h-full object-cover">
                                    </div>
                                </td>

                                <!-- Title & SKU -->
                                <td class="p-3">
                                    <a href="{{ route('admin.products.edit', $product->id) }}" class="font-black text-sm uppercase text-black hover:underline block truncate max-w-xs">
                                        {{ $product->title }}
                                    </a>
                                    <div class="flex items-center gap-2 mt-0.5">
                                        <span class="text-[10px] font-mono text-gray-500 font-bold">{{ $product->sku }}</span>
                                        @if($product->variants->count() > 0)
                                            <span class="text-[9px] bg-gray-100 border border-gray-300 px-1 py-0.2 font-mono">
                                                {{ $product->variants->count() }} variant(s)
                                            </span>
                                        @endif
                                    </div>
                                </td>

                                <!-- Collections -->
                                <td class="p-3">
                                    @if($product->collections->count() > 0)
                                        <div class="flex flex-wrap gap-1">
                                            @foreach($product->collections as $col)
                                                <span class="text-[9px] font-bold uppercase bg-gray-100 border border-gray-300 px-1.5 py-0.5">
                                                    {{ $col->title }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="text-[10px] text-gray-400 italic">Unassigned</span>
                                    @endif
                                </td>

                                <!-- Price (Inline Quick-Edit) -->
                                <td 
                                    class="p-3 font-mono transition-colors duration-500"
                                    x-data="{
                                        editing: false,
                                        priceEgp: '{{ $product->retail_price_minor ? (int)round($product->retail_price_minor / 100) : 0 }}',
                                        displayPrice: '{{ number_format($product->retail_price_minor / 100, 0) }} EGP',
                                        saved: false,
                                        save() {
                                            fetch('{{ route('admin.products.inline-edit', $product->id) }}', {
                                                method: 'POST',
                                                headers: {
                                                    'Content-Type': 'application/json',
                                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                                    'Accept': 'application/json'
                                                },
                                                body: JSON.stringify({ field: 'retail_price_minor', value: this.priceEgp })
                                            })
                                            .then(r => r.json())
                                            .then(data => {
                                                if(data.success) {
                                                    this.displayPrice = data.new_value_formatted;
                                                    this.saved = true;
                                                    setTimeout(() => { this.saved = false; }, 1200);
                                                }
                                                this.editing = false;
                                            })
                                            .catch(() => { this.editing = false; });
                                        }
                                    }"
                                    :class="saved ? 'bg-green-100' : ''"
                                >
                                    <div x-show="!editing" @click="editing = true; $nextTick(() => $refs.priceInput.focus())" class="cursor-pointer hover:underline flex items-center gap-1 group" title="Click to quick-edit price">
                                        <span class="font-black text-black" x-text="displayPrice"></span>
                                        <span class="opacity-0 group-hover:opacity-100 text-[9px] text-gray-400">✎</span>
                                    </div>
                                    <div x-show="editing" x-cloak class="flex items-center gap-1">
                                        <input 
                                            x-ref="priceInput" 
                                            type="number" 
                                            x-model="priceEgp" 
                                            @keydown.enter="save()" 
                                            @keydown.escape="editing = false" 
                                            @blur="save()" 
                                            class="w-20 border-2 border-black p-1 text-xs font-mono font-bold bg-white focus:outline-none"
                                        >
                                        <span class="text-[10px] text-gray-400">EGP</span>
                                    </div>
                                    @if($product->compare_at_price_minor)
                                        <div class="text-[10px] text-gray-400 line-through">
                                            {{ number_format($product->compare_at_price_minor / 100, 0) }} EGP
                                        </div>
                                    @endif
                                </td>

                                <!-- Stock (Inline Quick-Edit) -->
                                <td 
                                    class="p-3 font-mono transition-colors duration-500"
                                    x-data="{
                                        editing: false,
                                        stock: {{ $product->inventory }},
                                        saved: false,
                                        save() {
                                            fetch('{{ route('admin.products.inline-edit', $product->id) }}', {
                                                method: 'POST',
                                                headers: {
                                                    'Content-Type': 'application/json',
                                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                                    'Accept': 'application/json'
                                                },
                                                body: JSON.stringify({ field: 'inventory', value: this.stock })
                                            })
                                            .then(r => r.json())
                                            .then(data => {
                                                if(data.success) {
                                                    this.stock = parseInt(data.new_value);
                                                    this.saved = true;
                                                    setTimeout(() => { this.saved = false; }, 1200);
                                                }
                                                this.editing = false;
                                            })
                                            .catch(() => { this.editing = false; });
                                        }
                                    }"
                                    :class="saved ? 'bg-green-100' : ''"
                                >
                                    <div x-show="!editing" @click="editing = true; $nextTick(() => $refs.stockInput.focus())" class="cursor-pointer hover:underline flex items-center gap-1 group" title="Click to quick-edit stock">
                                        <template x-if="stock <= 0">
                                            <span class="text-red-600 bg-red-50 border border-red-200 px-1.5 py-0.5 text-[10px] font-bold">OUT OF STOCK</span>
                                        </template>
                                        <template x-if="stock > 0 && stock <= {{ $product->low_stock_threshold ?: 5 }}">
                                            <span class="text-amber-700 bg-amber-50 border border-amber-300 px-1.5 py-0.5 text-[10px] font-bold">⚠️ <span x-text="stock"></span> left</span>
                                        </template>
                                        <template x-if="stock > {{ $product->low_stock_threshold ?: 5 }}">
                                            <span class="text-green-800 font-bold"><span x-text="stock"></span> in stock</span>
                                        </template>
                                        <span class="opacity-0 group-hover:opacity-100 text-[9px] text-gray-400">✎</span>
                                    </div>
                                    <div x-show="editing" x-cloak>
                                        <input 
                                            x-ref="stockInput" 
                                            type="number" 
                                            x-model="stock" 
                                            @keydown.enter="save()" 
                                            @keydown.escape="editing = false" 
                                            @blur="save()" 
                                            class="w-16 border-2 border-black p-1 text-xs font-mono font-bold bg-white focus:outline-none"
                                        >
                                    </div>
                                </td>

                                <!-- Status -->
                                <td class="p-3">
                                    <form action="{{ route('admin.products.toggle-status', $product->id) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="text-[10px] font-bold uppercase px-2 py-0.5 border cursor-pointer {{ $product->status === 'active' ? 'bg-green-100 border-green-600 text-green-800 hover:bg-green-200' : 'bg-gray-100 border-gray-400 text-gray-700 hover:bg-gray-200' }}" title="Click to toggle status">
                                            {{ $product->status }} ⟳
                                        </button>
                                    </form>
                                </td>

                                <!-- Actions -->
                                <td class="p-3 text-right space-x-1">
                                    <a href="{{ route('products.show', $product->slug) }}" target="_blank" class="border border-black px-2 py-1 text-[10px] font-bold uppercase hover:bg-gray-100" title="View live page">
                                        ↗ View
                                    </a>
                                    <a href="{{ route('admin.products.edit', $product->id) }}" class="bg-black text-white px-2.5 py-1 text-[10px] font-bold uppercase hover:bg-gray-800">
                                        ✏ Edit
                                    </a>
                                    <form action="{{ route('admin.products.delete', $product->id) }}" method="POST" class="inline" onsubmit="return confirm('Permanently delete {{ addslashes($product->title) }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="border border-red-600 text-red-600 px-2 py-1 text-[10px] font-bold uppercase hover:bg-red-50">
                                            🗑
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="p-4 border-t-2 border-black bg-gray-50 flex items-center justify-between">
                {{ $products->links() }}
            </div>
        @else
            <div class="p-12 text-center text-gray-500">
                <span class="text-4xl block mb-2">🛍</span>
                <h3 class="font-bold text-sm uppercase tracking-wider">No Products Found</h3>
                <p class="text-xs text-gray-400 mt-1">Try adjusting your search criteria or add a new piece to the catalog.</p>
                <a href="{{ route('admin.products.create') }}" class="inline-block mt-4 bg-black text-white px-4 py-2 text-xs font-bold uppercase tracking-wider">
                    + Add Product Now
                </a>
            </div>
        @endif
    </div>

    <!-- CSV Import Modal -->
    <div 
        x-show="importModal" 
        x-cloak 
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4"
    >
        <div class="bg-white border-2 border-black max-w-lg w-full p-6 shadow-[8px_8px_0px_0px_rgba(0,0,0,1)] space-y-4" @click.outside="importModal = false">
            <div class="flex items-center justify-between border-b-2 border-black pb-3">
                <h3 class="text-lg font-black uppercase tracking-tight text-black">📤 Bulk Import Products (CSV)</h3>
                <button @click="importModal = false" class="text-black font-bold text-lg">✕</button>
            </div>

            <p class="text-xs text-gray-600 leading-relaxed">
                Upload a CSV spreadsheet with headers: <code class="bg-gray-100 px-1 py-0.5 font-mono text-[11px] font-bold">SKU, Name, Retail_Price_EGP, Inventory, Status</code>. Existing products matching by SKU will be updated; new SKUs will be created.
            </p>

            <form action="{{ route('admin.products.import') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-700 mb-1">Select CSV File *</label>
                    <input type="file" name="csv_file" required accept=".csv,text/csv" class="w-full border-2 border-black p-2.5 text-xs bg-white focus:outline-none">
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t">
                    <button type="button" @click="importModal = false" class="border border-black px-4 py-2 text-xs font-bold uppercase">
                        Cancel
                    </button>
                    <button type="submit" class="bg-black text-white px-6 py-2 text-xs font-bold uppercase tracking-wider hover:bg-gray-800 shadow-[3px_3px_0px_0px_rgba(0,0,0,1)]">
                        Upload & Process →
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

@push('scripts')
<script>
function productsManager() {
    return {
        selected: [],
        bulkActionType: 'activate',
        importModal: false,
        toggleSelectAll(e) {
            if (e.target.checked) {
                this.selected = @json($products->pluck('id')->map(fn($id) => (string)$id));
            } else {
                this.selected = [];
            }
        }
    };
}

function confirmBulkAction(form) {
    const action = form.elements['action'].value;
    if (action === 'delete') {
        return confirm('Are you sure you want to PERMANENTLY DELETE all selected products?');
    }
    return true;
}
</script>
@endpush
@endsection

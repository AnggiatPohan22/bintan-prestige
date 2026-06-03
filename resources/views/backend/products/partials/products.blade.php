            {{-- Product Name --}}
            <div>

                <label class="form-label">
                    Product Name
                    <span class="text-red-500">*</span>
                </label>

                <div class="relative">

                    <input
                        type="text"
                        name="name"
                        value="{{ old('name', $product->name ?? '') }}"
                        placeholder="Enter product name"
                        class="{{ $inputClass }}
                        {{ $errors->has('name')
                            ? $errorClass
                            : $normalClass }}"
                    >

                    @error('name')
                        <p class="form-error">
                            {{ $message }}
                        </p>
                    @enderror

                </div>

            </div>

            {{-- Slug --}}
            <div>

                <label class="block mb-2 text-sm font-semibold text-slate-700">
                    Slug
                </label>

                <input
                    type="text"
                    name="slug"
                    value="{{ old('slug', $product->slug ?? '') }}"
                    placeholder="Auto generate if empty"
                    class="{{ $inputClass }} {{ $normalClass }}"
                >

                <p class="mt-2 text-xs text-slate-400">
                    Optional — leave empty for auto generate
                </p>

            </div>

            {{-- Short Description --}}
            <div>

                <label class="block mb-2 text-sm font-semibold text-slate-700">
                    Short Description
                    <span class="text-red-500">*</span>
                </label>

                <textarea
                    name="short_description"
                    rows="4"
                    placeholder="Input short description"
                    class="{{ $inputClass }}
                    {{ $errors->has('short_description')
                        ? $errorClass
                        : $normalClass }}"
                >{{ old('short_description', $product->short_description ?? '') }}</textarea>

                @error('short_description')
                    <p class="mt-2 text-xs text-red-600 font-medium">
                        {{ $message }}
                    </p>
                @enderror

            </div>

            {{-- Meeting Point --}}
            <div>

                <label class="block mb-2 text-sm font-semibold text-slate-700">
                    Meeting Point
                    <span class="text-red-500">*</span>
                </label>

                <input
                    type="text"
                    name="meeting_point"
                    value="{{ old('meeting_point', $product->meeting_point ?? '') }}"
                    placeholder="Enter meeting point"
                    class="{{ $inputClass }}
                    {{ $errors->has('meeting_point')
                        ? $errorClass
                        : $normalClass }}"
                >

                @error('meeting_point')
                    <p class="mt-2 text-xs text-red-600 font-medium">
                        {{ $message }}
                    </p>
                @enderror

            </div>

            {{--Description --}}
            <div class="md:col-span-2">
                <label class="block font-medium mb-2">
                    Description
                    <span class="text-red-500">*</span>
                </label>
                @error('description')
                    <p class="text-red-500 text-sm mt-1">
                        {{ $message }}
                    </p>
                @enderror

                <textarea
                    name="description"
                    rows="5"
                    class="w-full rounded-lg border"
                    placeholder="Enter product description"
                    @error('description')
                        border-red-500
                    @else
                        border-gray-300
                    @enderror
                >{{ old('description', $product->description ?? '') }}</textarea>
            </div>

            {{--Duration --}}
            <div>
                <label class="block mb-2 text-sm font-semibold text-slate-700">
                    Duration
                    <span class="text-red-500">*</span>
                </label>

                <input
                    type="text"
                    name="duration"
                    value="{{ old('duration', $product->duration ?? '') }}"
                    placeholder="Enter duration"
                    class="{{ $inputClass }}
                    {{ $errors->has('duration')
                        ? $errorClass
                        : $normalClass }}"
                >
                @error('duration')
                    <p class="mt-2 text-xs text-red-600 font-medium">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            {{-- WhatsApp --}}
            <div>

                <label class="block mb-2 text-sm font-semibold text-slate-700">
                    WhatsApp Contact
                    <span class="text-red-500">*</span>
                </label>

                <input
                    type="text"
                    name="whatsapp_number"
                    value="{{ old('whatsapp_number', $product->whatsapp_number ?? '') }}"
                    placeholder="628xxxxxxxx"
                    class="{{ $inputClass }}
                    {{ $errors->has('whatsapp_number')
                        ? $errorClass
                        : $normalClass }}"
                >

                @error('whatsapp_number')
                    <p class="mt-2 text-xs text-red-600 font-medium">
                        {{ $message }}
                    </p>
                @enderror

            </div>

            {{-- Category --}}
            <div>
                <label class="block mb-2 font-medium">
                    Category
                    <span class="text-red-500">*</span>
                </label>

                <select name="category_id"
                        class="{{ $inputClass }}
                        {{ $errors->has('category_id')
                            ? $errorClass
                            : $normalClass }}">

                    <option value="">
                        Select Category
                    </option>

                    @foreach($categories as $category)
                        <option value="{{ $category->id }}"
                            @selected(old(
                                'category_id',
                                $product->category_id ?? ''
                            ) == $category->id)>
                            {{ $category->name }}
                        </option>
                    @endforeach

                </select>
            </div>

            {{-- Destination --}}
            <div>
                <label class="block mb-2 font-medium">
                    Destination
                    <span class="text-red-500">*</span>
                </label>

                <select name="destination_id"
                        class="{{ $inputClass }}
                        {{ $errors->has('destination_id')
                            ? $errorClass
                            : $normalClass }}">

                    <option value="">
                        Select Destination
                    </option>

                    @foreach($destinations as $destination)
                        <option value="{{ $destination->id }}"
                            @selected(old(
                                'destination_id',
                                $product->destination_id ?? ''
                            ) == $destination->id)>
                            {{ $destination->name }}
                        </option>
                    @endforeach

                </select>
            </div>

            {{-- IDR Price --}}
            <div>
                <label class="block mb-2 font-medium">
                    IDR Price
                    <span class="text-red-500">*</span>
                </label>

                <input type="number"
                    name="idr_price"
                    value="{{ old(
                        'idr_price',
                        $product->idr_price ?? ''
                    ) }}"
                    placeholder="500000"
                    class="{{ $inputClass }}
                    {{ $errors->has('idr_price')
                        ? $errorClass
                        : $normalClass }}">

                @error('idr_price')
                    <p class="text-red-500 text-sm mt-1">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            {{-- SGD Price --}}
            <div>
                <label class="block mb-2 font-medium">
                    SGD Price
                    <span class="text-red-500">*</span>
                </label>

                <input type="number"
                    name="sgd_price"
                    value="{{ old(
                        'sgd_price',
                        $product->sgd_price ?? ''
                    ) }}"
                    class="{{ $inputClass }}
                    {{ $errors->has('sgd_price')
                        ? $errorClass
                        : $normalClass }}">

                @error('sgd_price')
                    <p class="text-red-500 text-sm mt-1">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            {{-- Featured --}}
            <div class="rounded-xl border border-slate-200 p-4 shadow-sm">

                <div class="flex items-center justify-between">

                    <div>
                        <h4 class="font-semibold text-slate-700">
                            Featured Product
                        </h4>

                        <p class="text-sm text-slate-500">
                            Show on featured section
                        </p>
                    </div>

                    <label class="inline-flex cursor-pointer">

                        <input
                            type="hidden"
                            name="is_featured"
                            value="0"
                        >

                        <input
                            type="checkbox"
                            name="is_featured"
                            value="1"
                            class="sr-only peer"
                            {{ old(
                                'is_featured',
                                $product->is_featured ?? false
                            ) ? 'checked' : '' }}
                        >

                        <div class="relative w-12 h-6 bg-slate-300 rounded-full
                            peer peer-checked:bg-emerald-600
                            after:content-['']
                            after:absolute after:left-[2px]
                            after:top-[2px]
                            after:bg-white after:h-5 after:w-5
                            after:rounded-full after:transition-all
                            peer-checked:after:translate-x-full">
                        </div>

                    </label>

                </div>

            </div>

            {{-- Status --}}
            <div>
                <label class="block mb-2 font-medium">
                    Status
                    <span class="text-red-500">*</span>
                </label>

                <select
                    name="status"
                    class="{{ $inputClass }}
                    {{ $errors->has('status')
                        ? $errorClass
                        : $normalClass }}"
                >

                    <option value="draft"
                        @selected(
                            old(
                                'status',
                                $product->status ?? 'draft'
                            ) == 'draft'
                        )>
                        Draft
                    </option>

                    <option value="published"
                        @selected(
                            old(
                                'status',
                                $product->status ?? ''
                            ) == 'published'
                        )>
                        Published
                    </option>

                </select>
            </div>

            {{-- Thumbnail Upload --}}
            <div class="md:col-span-2">

                <label class="block mb-2 font-medium">
                    Thumbnail
                </label>

                <input type="file"
                    name="thumbnail"
                    accept="image/jpeg,image/png,image/webp"
                    class="w-full border rounded-lg p-3">

                @error('thumbnail')
                    <p class="text-red-500 text-sm mt-1">
                        {{ $message }}
                    </p>
                @enderror
                <p class="mt-2 text-xs text-slate-400">
                    Leave thumbnail empty to auto-use first gallery image.
                </p>

            </div>

            {{-- Gallery Images --}}
            <div class="md:col-span-2">

                <label class="block mb-2 font-medium">
                    Gallery Images
                </label>

                <input
                    type="file"
                    name="gallery[]"
                    multiple
                    accept="image/jpeg,image/png,image/webp"
                    class="w-full border rounded-lg p-3">

                @error('gallery.*')
                    <p class="text-red-500 text-sm mt-1">
                        {{ $message }}
                    </p>
                @enderror

                <p class="text-sm text-gray-500 mt-2">
                    Multiple upload supported.
                    JPG, PNG, or WEBP up to 2MB each. Auto optimized to WEBP.
                </p>

            </div>

            @if(isset($product)
            && $product->images->count())

            <div class="md:col-span-2">

                <label class="block mb-4 font-medium">
                    Product Gallery
                </label>

                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">

                    @foreach($product->images as $image)

                        <div class="relative overflow-hidden rounded-xl border bg-white shadow">

                            @if($image->image_url)
                                <img
                                    src="{{ $image->image_url }}"
                                    class="w-full aspect-[16/9] object-cover"
                                    alt="{{ $product->name }}">
                            @else
                                <div class="flex aspect-[16/9] w-full items-center justify-center bg-slate-100 text-xs font-semibold uppercase text-slate-400">
                                    No Image
                                </div>
                            @endif

                            <div class="space-y-2 p-3">
                                @if(($product->thumbnail ?? null) === $image->image)
                                    <span class="inline-flex rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">
                                        Current Thumbnail
                                    </span>
                                @else
                                    <button
                                        type="submit"
                                        form="set-thumbnail-{{ $image->id }}"
                                        class="w-full rounded-lg border border-emerald-200 px-3 py-2 text-xs font-semibold text-emerald-700 hover:bg-emerald-50"
                                    >
                                        Set as Thumbnail
                                    </button>
                                @endif

                                <button
                                    type="submit"
                                    form="delete-gallery-image-{{ $image->id }}"
                                    class="w-full rounded-lg border border-red-200 px-3 py-2 text-xs font-semibold text-red-600 hover:bg-red-50"
                                    onclick="return confirm('Delete this gallery image?')"
                                >
                                    Delete Image
                                </button>
                            </div>


                        </div>

                    @endforeach

                </div>

            </div>

            @endif
            

            {{-- Preview Image --}}
            @if(isset($product) && $product->thumbnail_url)

            <div class="md:col-span-2">

                <label class="block mb-2 font-medium">
                    Current Thumbnail
                </label>

                <img src="{{ $product->thumbnail_url }}"
                    class="w-48 rounded-lg border shadow">

                <button
                    type="submit"
                    form="delete-product-thumbnail"
                    class="mt-3 rounded-lg border border-red-200 px-4 py-2 text-sm font-semibold text-red-600 hover:bg-red-50"
                    onclick="return confirm('Remove current thumbnail?')"
                >
                    Remove Thumbnail
                </button>

            </div>

            @endif

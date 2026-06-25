<div class="space-y-4" x-data="pricingTableBlock(@js($block->data['plans'] ?? []))">
    <div>
        <label class="admin-form-label">Section Heading</label>
        <input type="text" name="data[heading]" value="{{ old('data.heading', $block->data['heading'] ?? '') }}" class="admin-input" maxlength="255" placeholder="Choose your package">
    </div>
    <div>
        <label class="admin-form-label">Introduction</label>
        <textarea name="data[intro]" rows="2" class="admin-textarea" maxlength="1000" placeholder="Optional pricing context">{{ old('data.intro', $block->data['intro'] ?? '') }}</textarea>
    </div>

    <div class="flex items-center justify-between gap-3">
        <span class="admin-form-label mb-0">Plans</span>
        <button type="button" x-on:click="addPlan()" x-bind:disabled="plans.length >= 6" class="admin-btn-soft px-3 py-1.5 text-xs">+ Add Plan</button>
    </div>

    <template x-for="(plan, planIndex) in plans" :key="planIndex">
        <div class="space-y-3 rounded-lg border border-admin p-3">
            <div class="grid grid-cols-1 gap-3 md:grid-cols-4">
                <div>
                    <label class="admin-form-label">Plan Name</label>
                    <input type="text" :name="`data[plans][${planIndex}][name]`" x-model="plan.name" class="admin-input" maxlength="150" placeholder="Island Explorer">
                </div>
                <div>
                    <label class="admin-form-label">Currency</label>
                    <input type="text" :name="`data[plans][${planIndex}][currency]`" x-model="plan.currency" class="admin-input" maxlength="10" placeholder="IDR">
                </div>
                <div>
                    <label class="admin-form-label">Price</label>
                    <input type="text" :name="`data[plans][${planIndex}][price]`" x-model="plan.price" class="admin-input" maxlength="50" placeholder="1,500,000">
                </div>
                <div>
                    <label class="admin-form-label">Period</label>
                    <input type="text" :name="`data[plans][${planIndex}][period]`" x-model="plan.period" class="admin-input" maxlength="50" placeholder="per person">
                </div>
            </div>

            <div>
                <div class="flex items-center justify-between gap-3">
                    <label class="admin-form-label mb-0">Features</label>
                    <button type="button" x-on:click="addFeature(planIndex)" x-bind:disabled="plan.features.length >= 20" class="text-xs font-semibold text-indigo-600 hover:underline">+ Add Feature</button>
                </div>
                <template x-for="(feature, featureIndex) in plan.features" :key="featureIndex">
                    <div class="mt-2 flex gap-2">
                        <input type="text" :name="`data[plans][${planIndex}][features][${featureIndex}]`" x-model="plan.features[featureIndex]" class="admin-input" maxlength="255" placeholder="Hotel transfer included">
                        <button type="button" x-on:click="removeFeature(planIndex, featureIndex)" class="text-xs text-red-500 hover:underline">Remove</button>
                    </div>
                </template>
            </div>

            <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                <div>
                    <label class="admin-form-label">Button Text</label>
                    <input type="text" :name="`data[plans][${planIndex}][button_text]`" x-model="plan.button_text" class="admin-input" maxlength="100" placeholder="Book now">
                </div>
                <div>
                    <label class="admin-form-label">Button URL</label>
                    <input type="text" :name="`data[plans][${planIndex}][button_url]`" x-model="plan.button_url" class="admin-input" placeholder="/contact">
                </div>
            </div>

            <div class="flex items-center justify-between gap-3">
                <label class="flex items-center gap-2 text-sm font-medium text-admin-secondary">
                    <input type="hidden" :name="`data[plans][${planIndex}][featured]`" value="0">
                    <input type="checkbox" :name="`data[plans][${planIndex}][featured]`" value="1" x-model="plan.featured" class="rounded border-admin">
                    Highlight this plan
                </label>
                <button type="button" x-on:click="removePlan(planIndex)" class="text-xs text-red-500 hover:underline">Remove Plan</button>
            </div>
        </div>
    </template>

    <p x-show="plans.length === 0" class="text-sm text-admin-secondary">No pricing plans yet.</p>
</div>

<script>
function pricingTableBlock(initialPlans) {
    return {
        plans: Array.isArray(initialPlans)
            ? initialPlans.map(plan => ({ ...plan, features: Array.isArray(plan.features) ? plan.features : [] }))
            : [],
        addPlan() {
            if (this.plans.length < 6) {
                this.plans.push({ name: '', currency: 'IDR', price: '', period: 'per person', features: [], button_text: '', button_url: '', featured: false });
            }
        },
        removePlan(index) { this.plans.splice(index, 1); },
        addFeature(planIndex) {
            if (this.plans[planIndex].features.length < 20) this.plans[planIndex].features.push('');
        },
        removeFeature(planIndex, featureIndex) { this.plans[planIndex].features.splice(featureIndex, 1); },
    };
}
</script>

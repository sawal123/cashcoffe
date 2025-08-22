<x-app-layout>
    {{-- <x-toastr /> --}}

    </div>
    <span class="mb-3 font-bold text-2xl">Menu Create</span>
    <div class="grid grid-cols-1 md:grid-cols-12 gap-6">
        <div class="md:col-span-12 2xl:col-span-12">
            <div class="card rounded-lg border-0">
                <div class="grid grid-cols-1 2xl:grid-cols-12">
                    <div class="xl:col-span-12 2xl:col-span-12">
                        <div class="card-body p-6">

                            <div class="grid grid-cols-12 gap-5">
                                <div class="col-span-12">
                                    <div class="card border-0">
                                        <div class="card-header">
                                            <h5 class="text-lg font-semibold mb-0">Input Custom Styles</h5>
                                        </div>
                                        <div class="card-body">
                                            <form class="grid grid-cols-12 gap-4">
                                                <div class="md:col-span-6 col-span-12">
                                                    <label class="form-label">Input with Placeholder</label>
                                                    <input type="text" name="#0" class="form-control"
                                                        value="info@gmail.com" required>
                                                </div>
                                                <div class="md:col-span-6 col-span-12">
                                                    <label class="form-label">Medium Size File Input </label>
                                                    <input
                                                        class="border border-neutral-200 dark:border-neutral-600 w-full rounded-lg"
                                                        type="file" name="#0">
                                                </div>
                                                <div class="md:col-span-6 col-span-12">
                                                    <label class="form-label">Input with Icon</label>
                                                    <input type="email" name="#0" class="form-control"
                                                        placeholder="Enter Email" required>
                                                </div>
                                                <div class="md:col-span-6 col-span-12">
                                                    <label class="form-label">Input with Payment </label>
                                                    <div class="flex">
                                                        <span
                                                            class="inline-flex items-center px-3 border rounded-e-0 border-e-0 rounded-s-md border-neutral-200 dark:border-neutral-600">
                                                            <img src="{{ asset('assets/images/card/payment-icon.png') }}"
                                                                alt="image">
                                                        </span>
                                                        <input type="text"
                                                            class="form-control grow rounded-ss-none rounded-es-none"
                                                            placeholder="Card Number">
                                                    </div>
                                                </div>
                                                <div class="md:col-span-6 col-span-12">
                                                    <label class="form-label">Input with Phone </label>
                                                    <div class="flex">
                                                        <select
                                                            class="form-select flex-grow-0 rounded-se-none rounded-ee-none border-e-0 w-auto">
                                                            <option>US</option>
                                                            <option>US</option>
                                                            <option>US</option>
                                                            <option>US</option>
                                                        </select>
                                                        <input type="text" name="#0"
                                                            class="form-control grow rounded-ss-none rounded-es-none"
                                                            placeholder="+1 (555) 000-0000">
                                                    </div>
                                                </div>
                                                <div class="md:col-span-6 col-span-12">
                                                    <label class="form-label">Input</label>
                                                    <div class="flex">
                                                        <input type="text"
                                                            class="form-control grow rounded-se-none rounded-ee-none"
                                                            placeholder="www.random.com">
                                                        <button type="button"
                                                            class="inline-flex items-center px-3 border rounded-s-0 border-s-0 rounded-e-md border-neutral-200 dark:border-neutral-600"><iconify-icon
                                                                icon="lucide:copy"></iconify-icon>Copy</button>
                                                    </div>
                                                </div>
                                                <div class="col-span-12">
                                                    <button class="btn btn-primary-600" type="submit">Submit
                                                        form</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

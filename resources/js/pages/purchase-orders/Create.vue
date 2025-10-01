<template>
  <div class="bg-[#DFECF2] min-h-screen">
    <div class="pl-2 pt-6 pr-6 pb-6">
      <Breadcrumbs :items="breadcrumbs" />

      <div class="flex items-center justify-between mb-6">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Buat Purchase Order</h1>
          <div class="flex items-center mt-2 text-sm text-gray-500">
            <CreditCard class="w-4 h-4 mr-1" />
            Create new Purchase Order
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow-sm p-6">
        <form @submit.prevent="onSubmit" novalidate class="space-y-4">
          <!-- Form Layout -->
          <div class="space-y-4">
            <!-- Row 1: No. PO | Metode Pembayaran -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div class="floating-input">
                <div
                  class="floating-input-field bg-gray-50 text-gray-600 cursor-not-allowed"
                >
                  Akan di-generate otomatis
                </div>
                <label for="no_po" class="floating-label">No. Purchase Order</label>
              </div>
              <div>
                <CustomSelect
                  :model-value="form.metode_pembayaran ?? ''"
                  @update:modelValue="(val) => (form.metode_pembayaran = val as string)"
                  :options="[
                    { label: 'Transfer', value: 'Transfer' },
                    { label: 'Cek/Giro', value: 'Cek/Giro' },
                    { label: 'Kredit', value: 'Kredit' },
                  ]"
                  placeholder="Pilih Metode"
                  :class="{ 'border-red-500': errors.metode_pembayaran }"
                >
                  <template #label>
                    Metode Pembayaran<span class="text-red-500">*</span>
                  </template>
                </CustomSelect>
                <div v-if="errors.metode_pembayaran" class="text-red-500 text-xs mt-1">
                  {{ errors.metode_pembayaran }}
                </div>
              </div>
            </div>

            <!-- Row 2: Tipe PO | Nama Rekening(Supplier) / No Cek Giro / No Kartu Kredit -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div class="flex space-x-12 items-center">
                <label class="flex items-center">
                  <input
                    type="radio"
                    v-model="form.tipe_po"
                    value="Reguler"
                    class="h-4 w-4 text-[#7F9BE6] focus:ring-[#7F9BE6] border-gray-300"
                  />
                  <span class="ml-2 text-sm text-gray-700">Reguler</span>
                </label>
                <label class="flex items-center">
                  <input
                    type="radio"
                    v-model="form.tipe_po"
                    value="Lainnya"
                    class="h-4 w-4 text-[#7F9BE6] focus:ring-[#7F9BE6] border-gray-300"
                  />
                  <span class="ml-2 text-sm text-gray-700">Lainnya</span>
                </label>
              </div>
              <!-- Dynamic field based on payment method -->
              <div
                v-if="form.metode_pembayaran === 'Transfer' || !form.metode_pembayaran"
              >
                <!-- Customer selection for Refund Konsumen -->
                <div v-if="isRefundKonsumenPerihal">
                  <CustomSelect
                    :model-value="form.customer_id ?? ''"
                    @update:modelValue="(val) => handleCustomerChange(val as string)"
                    :options="customerOptions.map((c: any) => ({ label: c.nama_ap, value: String(c.id) }))"
                    :searchable="true"
                    @search="searchCustomers"
                    :disabled="!form.department_id"
                    placeholder="Pilih Customer"
                    :class="{ 'border-red-500': errors.customer_id }"
                  >
                    <template #label>
                      Nama Customer<span class="text-red-500">*</span>
                    </template>
                  </CustomSelect>
                  <div v-if="errors.customer_id" class="text-red-500 text-xs mt-1">
                    {{ errors.customer_id }}
                  </div>
                </div>
                <!-- Supplier selection for other cases -->
                <div v-else>
                  <CustomSelect
                    :model-value="form.supplier_id ?? ''"
                    @update:modelValue="(val) => handleSupplierChange(val as string)"
                    :options="supplierList.map((s: any) => ({ label: s.nama_supplier, value: String(s.id) }))"
                    :searchable="true"
                    @search="searchSuppliers"
                    :disabled="!form.department_id"
                    placeholder="Pilih Supplier"
                    :class="{ 'border-red-500': errors.supplier_id }"
                  >
                    <template #label>
                      Nama Rekening (Supplier)<span class="text-red-500">*</span>
                    </template>
                  </CustomSelect>
                  <div v-if="errors.supplier_id" class="text-red-500 text-xs mt-1">
                    {{ errors.supplier_id }}
                  </div>
                </div>
              </div>
              <div
                v-else-if="form.metode_pembayaran === 'Cek/Giro'"
                class="floating-input"
              >
                <input
                  type="text"
                  v-model="form.no_giro"
                  id="no_giro"
                  class="floating-input-field"
                  :class="{ 'border-red-500': errors.no_giro }"
                  placeholder=" "
                  required
                />
                <label for="no_giro" class="floating-label"
                  >No. Cek/Giro<span class="text-red-500">*</span></label
                >
                <div v-if="errors.no_giro" class="text-red-500 text-xs mt-1">
                  {{ errors.no_giro }}
                </div>
              </div>
              <div v-else-if="form.metode_pembayaran === 'Kredit'">
                <CustomSelect
                  :model-value="selectedCreditCardId ?? ''"
                  @update:modelValue="(val) => handleSelectCreditCard(val as string)"
                  :options="creditCardOptions.map((cc: any) => ({ label: cc.nama_pemilik, value: String(cc.id) }))"
                  :disabled="!form.department_id"
                  :searchable="true"
                  @search="searchCreditCards"
                  placeholder="Pilih Nama Rekening (Kredit)"
                >
                  <template #label>
                    Nama Rekening (Kredit)<span class="text-red-500">*</span>
                  </template>
                </CustomSelect>
                <div v-if="errors.no_kartu_kredit" class="text-red-500 text-xs mt-1">
                  {{ errors.no_kartu_kredit }}
                </div>
              </div>
            </div>

            <!-- Row 3: Tanggal | Nama Bank / Tanggal Giro -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div class="floating-input">
                <div
                  class="floating-input-field bg-gray-50 text-gray-600 cursor-not-allowed filled"
                >
                  {{ displayTanggal }}
                </div>
                <label class="floating-label">Tanggal</label>
              </div>
              <!-- Dynamic field based on payment method -->
              <div
                v-if="form.metode_pembayaran === 'Transfer' || !form.metode_pembayaran"
              >
                <!-- Customer bank fields for Refund Konsumen -->
                <div v-if="isRefundKonsumenPerihal">
                  <CustomSelect
                    :model-value="form.customer_bank_id ?? ''"
                    @update:modelValue="(val) => handleCustomerBankChange(val as string)"
                    :options="bankList.map((bank: any) => ({
                      label: `${bank.nama_bank} (${bank.singkatan})`,
                      value: String(bank.id)
                    }))"
                    placeholder="Pilih Bank"
                    :class="{ 'border-red-500': errors.customer_bank_id }"
                  >
                    <template #label>
                      Nama Bank<span class="text-red-500">*</span>
                    </template>
                  </CustomSelect>
                  <div v-if="errors.customer_bank_id" class="text-red-500 text-xs mt-1">
                    {{ errors.customer_bank_id }}
                  </div>
                </div>
                <!-- Supplier bank selection for other cases -->
                <div v-else>
                  <CustomSelect
                    :model-value="form.bank_id ?? ''"
                    @update:modelValue="(val) => handleBankChange(val as string)"
                    :options="selectedSupplierBankAccounts.map((account: any) => ({
                      label: `${account.bank_name} (${account.bank_singkatan})`,
                      value: String(account.bank_id)
                    }))"
                    placeholder="Pilih Bank"
                    :disabled="selectedSupplierBankAccounts.length === 0"
                    :class="{ 'border-red-500': errors.bank_id }"
                  >
                    <template #label>
                      Nama Bank<span class="text-red-500">*</span>
                    </template>
                  </CustomSelect>
                  <div v-if="errors.bank_id" class="text-red-500 text-xs mt-1">
                    {{ errors.bank_id }}
                  </div>
                </div>
              </div>
              <div
                v-else-if="form.metode_pembayaran === 'Cek/Giro'"
                class="floating-input"
              >
                <label class="block text-xs font-light text-gray-700 mb-1">
                  Tanggal Giro<span class="text-red-500">*</span>
                </label>
                <Datepicker
                  v-model="validTanggalGiro"
                  :key="`giro-${datePickerKey}`"
                  :input-class="[
                    'floating-input-field',
                    validTanggalGiro ? 'filled' : '',
                    errors.tanggal_giro ? 'border-red-500' : '',
                  ]"
                  placeholder=" "
                  :format="(date: string | Date) => {
                    if (!date) return '';
                    try {
                      const dateObj = new Date(date);
                      if (isNaN(dateObj.getTime())) return '';
                      return dateObj.toLocaleDateString('id-ID');
                    } catch {
                      return '';
                    }
                  }"
                  :enable-time-picker="false"
                  :auto-apply="true"
                  :close-on-auto-apply="true"
                  id="tanggal_giro"
                />
                <div v-if="errors.tanggal_giro" class="text-red-500 text-xs mt-1">
                  {{ errors.tanggal_giro }}
                </div>
              </div>
              <div v-else-if="form.metode_pembayaran === 'Kredit'" class="floating-input">
                <input
                  type="text"
                  :value="selectedCreditCardBankName"
                  id="nama_bank_kredit"
                  class="floating-input-field bg-gray-50 text-gray-600 cursor-not-allowed"
                  placeholder=" "
                  readonly
                />
                <label for="nama_bank_kredit" class="floating-label">Nama Bank</label>
              </div>
            </div>

            <!-- Row 4: Departemen | Nama Rekening (Refund) / No Rekening (Supplier) / Tanggal Cair -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <CustomSelect
                  :model-value="form.department_id ?? ''"
                  @update:modelValue="(val) => (form.department_id = val as any)"
                  :options="departemenList.map((d: any) => ({ label: d.name, value: String(d.id) }))"
                  :disabled="(departemenList || []).length === 1"
                  placeholder="Pilih Departemen"
                  :class="{ 'border-red-500': errors.department_id }"
                >
                  <template #label>
                    Departemen<span class="text-red-500">*</span>
                  </template>
                </CustomSelect>
                <div v-if="errors.department_id" class="text-red-500 text-xs mt-1">
                  {{ errors.department_id }}
                </div>
              </div>
              <!-- Dynamic field based on payment method -->
              <div
                v-if="form.metode_pembayaran === 'Transfer' || !form.metode_pembayaran"
                class="floating-input"
              >
                <!-- Customer account name for Refund Konsumen (paired with Departemen) -->
                <div v-if="isRefundKonsumenPerihal">
                  <input
                    type="text"
                    v-model="form.customer_nama_rekening"
                    id="customer_nama_rekening"
                    class="floating-input-field"
                    :class="{ 'border-red-500': errors.customer_nama_rekening }"
                    placeholder=" "
                    required
                  />
                  <label for="customer_nama_rekening" class="floating-label">
                    Nama Rekening<span class="text-red-500">*</span>
                  </label>
                  <div
                    v-if="errors.customer_nama_rekening"
                    class="text-red-500 text-xs mt-1"
                  >
                    {{ errors.customer_nama_rekening }}
                  </div>
                </div>
                <!-- Supplier account number for other cases -->
                <div v-else>
                  <input
                    type="text"
                    v-model="form.no_rekening"
                    id="no_rekening"
                    class="floating-input-field bg-gray-50 text-gray-600 cursor-not-allowed"
                    :class="{ 'border-red-500': errors.no_rekening }"
                    placeholder=" "
                    readonly
                  />
                  <label for="no_rekening" class="floating-label">
                    No. Rekening/VA<span class="text-red-500">*</span>
                  </label>
                  <div v-if="errors.no_rekening" class="text-red-500 text-xs mt-1">
                    {{ errors.no_rekening }}
                  </div>
                </div>
              </div>
              <div
                v-else-if="form.metode_pembayaran === 'Cek/Giro'"
                class="floating-input"
              >
                <label class="block text-xs font-light text-gray-700 mb-1">
                  Tanggal Cair<span class="text-red-500">*</span>
                </label>
                <Datepicker
                  v-model="validTanggalCair"
                  :key="`cair-${datePickerKey}`"
                  :input-class="[
                    'floating-input-field',
                    validTanggalCair ? 'filled' : '',
                    errors.tanggal_cair ? 'border-red-500' : '',
                  ]"
                  placeholder=" "
                  :format="(date: string | Date) => {
                    if (!date) return '';
                    try {
                      const dateObj = new Date(date);
                      if (isNaN(dateObj.getTime())) return '';
                      return dateObj.toLocaleDateString('id-ID');
                    } catch {
                      return '';
                    }
                  }"
                  :enable-time-picker="false"
                  :auto-apply="true"
                  :close-on-auto-apply="true"
                  id="tanggal_cair"
                />
                <div v-if="errors.tanggal_cair" class="text-red-500 text-xs mt-1">
                  {{ errors.tanggal_cair }}
                </div>
              </div>
              <div v-else-if="form.metode_pembayaran === 'Kredit'" class="floating-input">
                <input
                  type="text"
                  v-model="form.no_kartu_kredit"
                  id="no_kartu_kredit_display"
                  class="floating-input-field bg-gray-50 text-gray-600 cursor-not-allowed"
                  :class="{ 'border-red-500': errors.no_kartu_kredit }"
                  placeholder=" "
                  readonly
                />
                <label for="no_kartu_kredit_display" class="floating-label">
                  No. Kartu Kredit<span class="text-red-500">*</span>
                </label>
                <div v-if="errors.no_kartu_kredit" class="text-red-500 text-xs mt-1">
                  {{ errors.no_kartu_kredit }}
                </div>
              </div>
            </div>

            <!-- Row 5: Perihal | No Rekening (Refund Konsumen) -->
            <div
              v-if="isRefundKonsumenPerihal"
              class="grid grid-cols-1 md:grid-cols-2 gap-6"
            >
              <div>
                <CustomSelect
                  :model-value="form.perihal_id ?? ''"
                  @update:modelValue="(val) => (form.perihal_id = val as any)"
                  :options="perihalList.map((p: any) => ({ label: p.nama, value: String(p.id) }))"
                  placeholder="Pilih Perihal"
                  :class="{ 'border-red-500': errors.perihal_id }"
                >
                  <template #label> Perihal<span class="text-red-500">*</span> </template>
                  <template #suffix>
                    <span
                      class="inline-flex items-center justify-center w-6 h-6 rounded-md text-white bg-blue-500 hover:bg-blue-600 focus:outline-none"
                      @click.stop="showAddPerihalModal = true"
                      title="Tambah Perihal"
                      role="button"
                      tabindex="0"
                    >
                      <svg
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 24 24"
                        fill="currentColor"
                        class="w-4 h-4"
                      >
                        <path
                          fill-rule="evenodd"
                          d="M12 4.5a.75.75 0 01.75.75v6h6a.75.75 0 010 1.5h-6v6a.75.75 0 01-1.5 0v-6h-6a.75.75 0 010-1.5h6v-6A.75.75 0 0112 4.5z"
                          clip-rule="evenodd"
                        />
                      </svg>
                    </span>
                  </template>
                </CustomSelect>
                <div v-if="errors.perihal_id" class="text-red-500 text-xs mt-1">
                  {{ errors.perihal_id }}
                </div>
              </div>
              <div class="floating-input">
                <input
                  type="text"
                  v-model="form.customer_no_rekening"
                  id="customer_no_rekening"
                  class="floating-input-field"
                  :class="{ 'border-red-500': errors.customer_no_rekening }"
                  placeholder=" "
                  required
                />
                <label for="customer_no_rekening" class="floating-label">
                  No. Rekening<span class="text-red-500">*</span>
                </label>
                <div v-if="errors.customer_no_rekening" class="text-red-500 text-xs mt-1">
                  {{ errors.customer_no_rekening }}
                </div>
              </div>
            </div>

            <!-- Row 5: Perihal | Note (for non-Refund Konsumen) -->
            <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <CustomSelect
                  :model-value="form.perihal_id ?? ''"
                  @update:modelValue="(val) => (form.perihal_id = val as any)"
                  :options="perihalList.map((p: any) => ({ label: p.nama, value: String(p.id) }))"
                  placeholder="Pilih Perihal"
                  :class="{ 'border-red-500': errors.perihal_id }"
                >
                  <template #label> Perihal<span class="text-red-500">*</span> </template>
                  <template #suffix>
                    <span
                      class="inline-flex items-center justify-center w-6 h-6 rounded-md text-white bg-blue-500 hover:bg-blue-600 focus:outline-none"
                      @click.stop="showAddPerihalModal = true"
                      title="Tambah Perihal"
                      role="button"
                      tabindex="0"
                    >
                      <svg
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 24 24"
                        fill="currentColor"
                        class="w-4 h-4"
                      >
                        <path
                          fill-rule="evenodd"
                          d="M12 4.5a.75.75 0 01.75.75v6h6a.75.75 0 010 1.5h-6v6a.75.75 0 01-1.5 0v-6h-6a.75.75 0 010-1.5h6v-6A.75.75 0 0112 4.5z"
                          clip-rule="evenodd"
                        />
                      </svg>
                    </span>
                  </template>
                </CustomSelect>
                <div v-if="errors.perihal_id" class="text-red-500 text-xs mt-1">
                  {{ errors.perihal_id }}
                </div>
              </div>
              <div class="floating-input">
                <textarea
                  v-model="form.note"
                  id="note"
                  class="floating-input-field resize-none"
                  placeholder=" "
                  rows="3"
                ></textarea>
                <label for="note" class="floating-label">Note</label>
              </div>
            </div>

            <!-- Row 6: No Invoice / No Ref Termin or Note (Refund) -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <!-- No Ref Termin for Lainnya -->
              <div v-if="form.tipe_po === 'Lainnya'">
                <div class="space-y-2">
                  <CustomSelect
                    :model-value="form.termin_id ?? ''"
                    @update:modelValue="(val) => handleTerminChange(val as any)"
                    :options="terminList.map((t: any) => ({
                      label: t.no_referensi,
                      value: String(t.id),
                      disabled: t.status === 'completed'
                    }))"
                    placeholder="Pilih Termin"
                    :class="{ 'border-red-500': errors.termin_id }"
                    :searchable="true"
                    @search="searchTermins"
                  >
                    <template #label>
                      No Ref Termin<span class="text-red-500">*</span>
                    </template>
                    <template #suffix>
                      <span
                        class="inline-flex items-center justify-center w-6 h-6 rounded-md text-white bg-blue-500 hover:bg-blue-600 focus:outline-none"
                        @click.stop="showAddTerminModal = true"
                        title="Tambah Termin"
                        role="button"
                        tabindex="0"
                      >
                        <svg
                          xmlns="http://www.w3.org/2000/svg"
                          viewBox="0 0 24 24"
                          fill="currentColor"
                          class="w-4 h-4"
                        >
                          <path
                            fill-rule="evenodd"
                            d="M12 4.5a.75.75 0 01.75.75v6h6a.75.75 0 010 1.5h-6v6a.75.75 0 01-1.5 0v-6h-6a.75.75 0 010-1.5h6v-6A.75.75 0 0112 4.5z"
                            clip-rule="evenodd"
                          />
                        </svg>
                      </span>
                    </template>
                  </CustomSelect>

                  <!-- Termin Info Display -->

                  <div v-if="errors.termin_id" class="text-red-500 text-xs mt-1">
                    {{ errors.termin_id }}
                  </div>
                </div>
              </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <!-- No Invoice for both Reguler and Lainnya -->
              <div class="floating-input">
                <input
                  type="text"
                  v-model="form.no_invoice"
                  id="no_invoice"
                  class="floating-input-field"
                  :class="{ 'border-red-500': errors.no_invoice }"
                  placeholder=" "
                />
                <label for="no_invoice" class="floating-label"> No. Invoice </label>
                <div v-if="errors.no_invoice" class="text-red-500 text-xs mt-1">
                  {{ errors.no_invoice }}
                </div>
              </div>
              <!-- Note pairs with No Invoice when Refund Konsumen -->
              <div v-if="isRefundKonsumenPerihal" class="floating-input">
                <textarea
                  v-model="form.note"
                  id="note"
                  class="floating-input-field resize-none"
                  placeholder=" "
                  rows="3"
                ></textarea>
                <label for="note" class="floating-label">Note</label>
              </div>
            </div>

            <!-- Row 7: Harga (as Nominal for Refund) / Harga for Lainnya -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <!-- Harga for Reguler -->
              <div v-if="form.tipe_po === 'Reguler'" class="floating-input">
                <input
                  type="text"
                  v-model="displayHarga"
                  id="harga"
                  class="floating-input-field"
                  :class="{ 'border-red-500': errors.harga }"
                  placeholder=" "
                  required
                  :readonly="!isSpecialPerihal"
                  inputmode="decimal"
                  @keydown="allowNumericKeydown"
                />
                <label for="harga" class="floating-label">
                  {{ isRefundKonsumenPerihal ? "Nominal" : "Harga"
                  }}<span class="text-red-500">*</span>
                </label>
                <div v-if="errors.harga" class="text-red-500 text-xs mt-1">
                  {{ errors.harga }}
                </div>
              </div>

              <!-- Harga for Lainnya -->
              <div v-if="form.tipe_po === 'Lainnya'" class="floating-input">
                <div
                  class="floating-input-field bg-gray-50 text-gray-600 cursor-not-allowed filled"
                >
                  {{ displayHarga || "0" }}
                </div>
                <label for="harga_lainnya" class="floating-label">
                  Harga<span class="text-red-500">*</span>
                </label>
                <div v-if="errors.harga" class="text-red-500 text-xs mt-1">
                  {{ errors.harga }}
                </div>
              </div>
            </div>

            <!-- Row 8: Detail Keperluan -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div class="floating-input">
                <textarea
                  v-model="form.detail_keperluan"
                  id="detail_keperluan"
                  class="floating-input-field resize-none"
                  placeholder=" "
                  rows="3"
                ></textarea>
                <label for="detail_keperluan" class="floating-label"
                  >Detail Keperluan</label
                >
              </div>
            </div>
          </div>

          <!-- Khusus Staff Toko & Kepala Toko: Upload Dokumen Draft Invoice (Hanya untuk Tipe Reguler) -->
          <div
            v-if="isStaffToko && form.tipe_po === 'Reguler'"
            class="grid grid-cols-1 gap-6"
          >
            <FileUpload
              v-model="dokumenFile"
              label="Draft Invoice"
              :required="true"
              accept=".pdf,.jpg,.jpeg,.png"
              :max-size="50 * 1024 * 1024"
              drag-text="Bawa berkas ke area ini (maks. 50 MB) - Hanya file JPG, JPEG, PNG, dan PDF"
              @error="(message) => addError(message)"
            />
            <div v-if="errors.dokumen" class="text-red-500 text-xs mt-1">
              {{ errors.dokumen }}
            </div>
          </div>

          <hr class="my-6" />
        </form>

        <!-- Grid/List Barang - Outside the form to prevent submission conflicts -->
        <PurchaseOrderBarangGrid
          ref="barangGridRef"
          v-model:items="barangList"
          v-model:diskon="form.diskon"
          v-model:ppn="form.ppn"
          v-model:pph="form.pph_id"
          :pphList="pphList"
          @add-pph="onAddPph"
          :nominal="isSpecialPerihal ? form.harga : undefined"
          :form="form"
          :selected-perihal-name="selectedPerihalName"
        />
        <div v-if="errors.barang" class="text-red-500 text-xs mt-1">
          {{ errors.barang }}
        </div>

        <div class="flex justify-start gap-3 pt-6 border-t border-gray-200">
          <button
            type="button"
            class="px-6 py-2 text-sm font-medium text-white bg-[#7F9BE6] border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors flex items-center gap-2"
            @click="showSubmitConfirmation"
            :disabled="loading"
          >
            <svg
              fill="#E6E6E6"
              height="24"
              viewBox="0 0 24 24"
              width="24"
              xmlns="http://www.w3.org/2000/svg"
              class="w-5 h-5"
            >
              <path
                d="M17 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4zm-5 16c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm3-10H5V5h10v4z"
              />
            </svg>
            Kirim
          </button>
          <button
            type="button"
            class="px-6 py-2 text-sm font-medium text-white bg-blue-300 border border-transparent rounded-md hover:bg-blue-400 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-colors flex items-center gap-2"
            @click="onSaveDraft"
            :disabled="loading"
          >
            <svg
              xmlns="http://www.w3.org/2000/svg"
              fill="none"
              viewBox="0 0 24 24"
              stroke-width="1.5"
              stroke="currentColor"
              class="w-5 h-5"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0 1 11.186 0Z"
              />
            </svg>
            Simpan Draft
          </button>
          <button
            type="button"
            class="px-6 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors flex items-center gap-2"
            @click="goBack"
            :disabled="loading"
          >
            <svg
              xmlns="http://www.w3.org/2000/svg"
              fill="none"
              viewBox="0 0 24 24"
              stroke-width="1.5"
              stroke="currentColor"
              class="w-5 h-5"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M6 18L18 6M6 6l12 12"
              />
            </svg>
            Batal
          </button>
        </div>

        <PerihalQuickAddModal
          v-if="showAddPerihalModal"
          @close="showAddPerihalModal = false"
          @created="handlePerihalCreated"
        />

        <TerminQuickAddModal
          v-if="showAddTerminModal"
          @close="showAddTerminModal = false"
          @created="handleTerminCreated"
          :department-options="departemenList"
          :department-id="form.department_id as any"
        />

        <!-- Confirm Dialog -->
        <ConfirmDialog
          :show="showConfirmDialog"
          :message="
            confirmAction === 'submit'
              ? 'Apakah Anda yakin ingin mengirim Purchase Order ini?'
              : ''
          "
          @confirm="onSubmit"
          @cancel="showConfirmDialog = false"
        />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch, watchEffect, nextTick } from "vue";
import { router } from "@inertiajs/vue3";
import PurchaseOrderBarangGrid from "../../components/purchase-orders/PurchaseOrderBarangGrid.vue";
import Breadcrumbs from "@/components/ui/Breadcrumbs.vue";
import CustomSelect from "@/components/ui/CustomSelect.vue";
import FileUpload from "@/components/ui/FileUpload.vue";
import PerihalQuickAddModal from "@/components/perihals/PerihalQuickAddModal.vue";
import TerminQuickAddModal from "@/components/termins/TerminQuickAddModal.vue";
import ConfirmDialog from "@/components/ui/ConfirmDialog.vue";
import { CreditCard } from "lucide-vue-next";
import axios from "axios";
import AppLayout from "@/layouts/AppLayout.vue";
import Datepicker from "@vuepic/vue-datepicker";
import "@vuepic/vue-datepicker/dist/main.css";
import { format } from "date-fns";

import { useMessagePanel } from "@/composables/useMessagePanel";
import { usePermissions } from "@/composables/usePermissions";
import { formatCurrency, parseCurrency } from "@/lib/currencyUtils";

defineOptions({ layout: AppLayout });

const breadcrumbs = [
  { label: "Home", href: "/dashboard" },
  { label: "Purchase Order", href: "/purchase-orders" },
  { label: "Create" },
];

// Master data from props (provided by Inertia controller)
const props = defineProps<{
  departments: any[];
  perihals: any[];
  suppliers: any[];
  banks: any[];
  pphs: any[];
  termins: any[];
}>();
const departemenList = ref(Array.isArray(props.departments) ? props.departments : []);
const perihalList = ref<any[]>(Array.isArray(props.perihals) ? props.perihals : []);
const supplierList = ref<any[]>([]);
let supplierSearchTimeout: ReturnType<typeof setTimeout>;

// Message panel
const { addSuccess, addError, clearAll } = useMessagePanel();
const terminList = ref<any[]>(Array.isArray(props.termins) ? props.termins : []);
// Transform PPH data to match the expected format in PurchaseOrderBarangGrid
const pphList = ref(
  (Array.isArray(props.pphs) ? props.pphs : []).map((pph: any) => ({
    id: pph.id, // Keep the ID for backend submission
    kode: pph.kode_pph,
    nama: pph.nama_pph,
    tarif: pph.tarif_pph ? pph.tarif_pph / 100 : 0, // Convert percentage to decimal
  }))
);

// Supplier bank accounts data
const selectedSupplierBankAccounts = ref<any[]>([]);
const selectedSupplier = ref<any>(null);
// Credit card list by department
const creditCardOptions = ref<any[]>([]);
const selectedCreditCardId = ref<string | null>(null);
const selectedCreditCardBankName = ref<string>("");
let creditCardSearchTimeout: ReturnType<typeof setTimeout>;

// Customer data for Refund Konsumen
const customerOptions = ref<any[]>([]);
const bankList = ref<any[]>(Array.isArray(props.banks) ? props.banks : []);
// Defensive: normalize lists to arrays to avoid runtime .map errors when props become non-arrays
watchEffect(() => {
  if (!Array.isArray(departemenList.value)) departemenList.value = [] as any[];
  if (!Array.isArray(perihalList.value)) perihalList.value = [] as any[];
  if (!Array.isArray(supplierList.value)) supplierList.value = [] as any[];
  if (!Array.isArray(terminList.value)) terminList.value = [] as any[];
  if (!Array.isArray(selectedSupplierBankAccounts.value))
    selectedSupplierBankAccounts.value = [] as any[];
  if (!Array.isArray(creditCardOptions.value)) creditCardOptions.value = [] as any[];
  if (!Array.isArray(customerOptions.value)) customerOptions.value = [] as any[];
  if (!Array.isArray(bankList.value)) bankList.value = [] as any[];
});
let customerSearchTimeout: ReturnType<typeof setTimeout>;

// Use permissions composable to detect user role
const { hasRole } = usePermissions();
const isStaffToko = computed(
  () =>
    hasRole("Staff Toko") ||
    hasRole("Staff Digital Marketing") ||
    hasRole("Kepala Toko") ||
    hasRole("Admin")
);

const form = ref({
  tipe_po: "Reguler",
  tanggal: new Date() as any, // Set ke tanggal saat ini
  department_id: "",
  perihal_id: "",
  supplier_id: "",
  no_invoice: "",
  harga: null as any,
  detail_keperluan: "",
  metode_pembayaran: "",
  bank_id: "",
  nama_rekening: "",
  no_rekening: "",
  no_kartu_kredit: "",
  note: "",
  no_giro: "",
  tanggal_giro: new Date() as any, // Set ke tanggal saat ini
  tanggal_cair: new Date() as any, // Set ke tanggal saat ini
  diskon: null as any,
  ppn: false,
  pph_id: [] as any[],
  termin: null as any,
  termin_id: null as any,
  nominal: null as any,
  keterangan: "",
  // Customer fields for Refund Konsumen
  customer_id: "",
  customer_bank_id: "",
  customer_nama_rekening: "",
  customer_no_rekening: "",
});

// Watch diskon and pph_id from child grid, force reset to 0/[] if uncheck
watch(
  () => form.value.diskon,
  (val) => {
    if (!val || val === null || val === undefined) {
      form.value.diskon = 0;
    }
  }
);
watch(
  () => form.value.pph_id,
  (val) => {
    if (!val || (Array.isArray(val) && val.length === 0)) {
      form.value.pph_id = [];
    }
  }
);

// Core reactive state used across template/watchers should be declared early
const barangList = ref<any[]>([]);
const loading = ref(false);
const dokumenFile = ref<File | null>(null);
const errors = ref<{ [key: string]: string }>({});
const barangGridRef = ref();
const showAddPerihalModal = ref(false);
const showAddTerminModal = ref(false);
// Confirmation dialog
const showConfirmDialog = ref(false);
const confirmAction = ref<string>("");
// Detect selected Perihal name and whether it is a special case
const selectedPerihalName = computed(() => {
  const id = form.value.perihal_id;
  const found = perihalList.value.find((p: any) => String(p.id) === String(id));
  return found ? String(found.nama || "") : "";
});

const isSpecialPerihal = computed(() => {
  const nama = selectedPerihalName.value?.toLowerCase();
  return (
    nama === "permintaan pembayaran ongkir" ||
    nama === "permintaan pembayaran refund konsumen"
  );
});

const isRefundKonsumenPerihal = computed(() => {
  const nama = selectedPerihalName.value?.toLowerCase();
  return nama === "permintaan pembayaran refund konsumen";
});

const specialBarangNama = computed(() => {
  const nama = selectedPerihalName.value?.toLowerCase();
  if (nama === "permintaan pembayaran refund konsumen")
    return "Pembayaran Refund Konsumen";
  if (nama === "permintaan pembayaran ongkir") return "Pembayaran Ongkir";
  return "";
});

// When special perihal selected, auto-fill barang list and allow manual harga
watch(
  () => form.value.perihal_id,
  () => {
    if (form.value.tipe_po === "Reguler" && isSpecialPerihal.value) {
      // Always set default single item as specified
      barangList.value = [
        {
          nama: specialBarangNama.value,
          qty: 1,
          satuan: "–",
          harga: Number(form.value.harga || 0),
        },
      ];
    } else if (form.value.tipe_po === "Reguler" && !isSpecialPerihal.value) {
      // Clear barang list when switching away from special perihal
      barangList.value = [];
    }
  }
);

// Keep item.harga synced with manual Harga field for special perihal
watch(
  () => form.value.harga,
  (newHarga) => {
    if (
      form.value.tipe_po === "Reguler" &&
      isSpecialPerihal.value &&
      Array.isArray(barangList.value) &&
      barangList.value.length > 0
    ) {
      barangList.value = [
        {
          ...barangList.value[0],
          harga: Number(newHarga || 0),
        },
      ];
    }
  }
);

// Watch for department changes to load customers for Refund Konsumen
watch(
  () => form.value.department_id,
  () => {
    if (form.value.department_id && isRefundKonsumenPerihal.value) {
      searchCustomers("");
    }
  }
);

// Watch for perihal changes to load customers for Refund Konsumen
watch(
  () => form.value.perihal_id,
  () => {
    if (form.value.department_id && isRefundKonsumenPerihal.value) {
      searchCustomers("");
    }
  }
);

watch(
  () => barangGridRef.value?.grandTotal,
  (newGrandTotal) => {
    // Cegah loop: hanya update jika tipe Reguler atau Lainnya, grandTotal valid, dan berbeda dengan harga form
    if (
      (form.value.tipe_po === "Reguler" || form.value.tipe_po === "Lainnya") &&
      typeof newGrandTotal === "number" &&
      !isNaN(newGrandTotal) &&
      newGrandTotal > 0 &&
      form.value.harga !== newGrandTotal
    ) {
      form.value.harga = newGrandTotal;
    }
  },
  { immediate: false }
);

// Also watch for changes in barang list, diskon, ppn, and pph that affect grand total
watch(
  [
    () => barangList.value,
    () => form.value.diskon,
    () => form.value.ppn,
    () => form.value.pph_id,
  ],
  () => {
    if (
      (form.value.tipe_po === "Reguler" || form.value.tipe_po === "Lainnya") &&
      barangGridRef.value?.grandTotal
    ) {
      // Small delay to ensure the grid has recalculated the grand total
      setTimeout(() => {
        if (barangGridRef.value?.grandTotal) {
          form.value.harga = barangGridRef.value.grandTotal;
        }
      }, 100);
    }
  },
  { deep: true }
);

// Immediate update when barang list changes (for better responsiveness)
watch(
  () => barangList.value.length,
  () => {
    if (
      (form.value.tipe_po === "Reguler" || form.value.tipe_po === "Lainnya") &&
      barangGridRef.value?.grandTotal
    ) {
      // Update immediately when items are added/removed
      form.value.harga = barangGridRef.value.grandTotal;
    }
  }
);

// Numeric keydown helper for Harga (prevent letters)
function allowNumericKeydown(event: KeyboardEvent) {
  const allowedKeys = [
    "Backspace",
    "Delete",
    "Tab",
    "Enter",
    "Escape",
    "ArrowLeft",
    "ArrowRight",
    "Home",
    "End",
    ",",
    ".",
    "0",
    "1",
    "2",
    "3",
    "4",
    "5",
    "6",
    "7",
    "8",
    "9",
  ];
  const isCtrlCombo = event.ctrlKey || event.metaKey;
  if (isCtrlCombo) return; // allow copy/paste/select all
  if (!allowedKeys.includes(event.key)) {
    event.preventDefault();
  }
}

// Handle case when barang list is empty
watch(
  () => barangList.value.length === 0,
  (isEmpty) => {
    if (isEmpty && form.value.tipe_po === "Reguler") {
      // When barang list is empty, set harga to 0 or base amount
      form.value.harga = 0;
    }
  }
);

// Watch for barangGridRef to become available and initialize harga
watch(
  () => barangGridRef.value,
  (newRef) => {
    if (newRef && form.value.tipe_po === "Reguler") {
      // When the grid component becomes available, initialize harga
      setTimeout(() => {
        if (newRef.grandTotal && typeof newRef.grandTotal === "number") {
          form.value.harga = newRef.grandTotal;
        }
      }, 100);
    }
  },
  { immediate: false }
);

// Watch for PO type changes to update harga field accordingly
watch(
  () => form.value.tipe_po,
  async (newTipe) => {
    if (newTipe === "Reguler") {
      // Update harga when switching to Reguler PO
      // Use a longer delay to ensure the barang grid is fully rendered
      setTimeout(() => {
        if (
          barangGridRef.value?.grandTotal &&
          typeof barangGridRef.value.grandTotal === "number"
        ) {
          form.value.harga = barangGridRef.value.grandTotal;
        } else {
          // If no grand total available yet, set to 0
          form.value.harga = 0;
        }
      }, 300);
    } else if (newTipe === "Lainnya") {
      // Update harga from grand total when switching to Lainnya PO
      setTimeout(() => {
        if (
          barangGridRef.value?.grandTotal &&
          typeof barangGridRef.value.grandTotal === "number"
        ) {
          form.value.harga = barangGridRef.value.grandTotal;
        } else {
          form.value.harga = 0;
        }
      }, 300);
      // Clear special perihal items when switching away from Reguler
      const hasSpecialItem = barangList.value.some(
        (item) =>
          item.nama === "Pembayaran Refund Konsumen" || item.nama === "Pembayaran Ongkir"
      );
      if (hasSpecialItem) {
        barangList.value = [];
      }

      // Load termins for the selected department if available
      if (form.value.department_id) {
        try {
          const response = await axios.get("/purchase-orders/termins/by-department", {
            params: { department_id: form.value.department_id },
          });
          if (response.data && response.data.success) {
            terminList.value = response.data.data || [];
          }
        } catch (error) {
          console.error("Error fetching termins by department:", error);
          // Fallback to filtering from props
          const filteredTermins = props.termins.filter(
            (termin: any) => termin.department_id == form.value.department_id
          );
          terminList.value = filteredTermins;
        }
      } else {
        terminList.value = [];
      }
    } else if (newTipe === "Anggaran") {
      // Clear harga when switching to Anggaran PO
      form.value.harga = null;
      // Clear special perihal items when switching away from Reguler
      const hasSpecialItem = barangList.value.some(
        (item) =>
          item.nama === "Pembayaran Refund Konsumen" || item.nama === "Pembayaran Ongkir"
      );
      if (hasSpecialItem) {
        barangList.value = [];
      }
    }
  }
);

// Load suppliers and termins by department on change
watch(
  () => form.value.department_id,
  async (deptId) => {
    // Clear selection and dependent fields
    form.value.supplier_id = "";
    form.value.bank_id = "";
    form.value.nama_rekening = "";
    form.value.no_rekening = "";
    selectedSupplierBankAccounts.value = [];
    selectedSupplier.value = null;

    if (!deptId) {
      supplierList.value = [];
      // Clear termin list if no department selected
      if (form.value.tipe_po === "Lainnya") {
        terminList.value = [];
      }
      return;
    }

    // Load suppliers for the department
    try {
      const { data } = await axios.get("/purchase-orders/suppliers/by-department", {
        params: { department_id: deptId },
      });
      supplierList.value = Array.isArray(data?.data) ? data.data : [];
    } catch {
      supplierList.value = [];
    }

    // Load termins for the department if PO type is Lainnya
    if (form.value.tipe_po === "Lainnya") {
      // Clear selected termin when department changes
      form.value.termin_id = null;

      try {
        const response = await axios.get("/purchase-orders/termins/by-department", {
          params: { department_id: deptId },
        });
        if (response.data && response.data.success) {
          terminList.value = response.data.data || [];
        }
      } catch (error) {
        console.error("Error fetching termins by department:", error);
        addError("Gagal mengambil data termin untuk departemen yang dipilih");
        // Fallback to filtering from props
        const filteredTermins = props.termins.filter(
          (termin: any) => termin.department_id == deptId
        );
        terminList.value = filteredTermins;
      }
    }
  }
);

function searchSuppliers(query: string) {
  clearTimeout(supplierSearchTimeout);
  supplierSearchTimeout = setTimeout(async () => {
    if (
      !form.value.department_id ||
      (form.value.metode_pembayaran !== "Transfer" && form.value.metode_pembayaran)
    )
      return;
    try {
      const { data } = await axios.get("/purchase-orders/suppliers/by-department", {
        params: { department_id: form.value.department_id, search: query, per_page: 50 },
      });
      supplierList.value = Array.isArray(data?.data) ? data.data : [];
    } catch {
      // ignore
    }
  }, 300);
}

// Watch department for Kredit method to load credit cards
watch(
  () => [form.value.department_id, form.value.metode_pembayaran] as const,
  async ([deptId, metode]) => {
    if (metode === "Kredit") {
      selectedCreditCardId.value = null;
      form.value.no_kartu_kredit = "";
      creditCardOptions.value = [];
      if (deptId) {
        try {
          const { data } = await axios.get("/credit-cards", {
            headers: { Accept: "application/json" },
            params: { department_id: deptId, status: "active", per_page: 1000 },
          });
          creditCardOptions.value = Array.isArray(data?.data) ? data.data : [];
        } catch {
          creditCardOptions.value = [];
        }
      }
    }
  },
  { immediate: true }
);

// Auto-select department when only one available
if (!form.value.department_id && (departemenList.value || []).length === 1) {
  form.value.department_id = String(departemenList.value[0].id);
}

// Initialize termin list based on selected department and PO type
if (form.value.tipe_po === "Lainnya") {
  if (form.value.department_id) {
    // Filter termin list by department on initial load
    const filteredTermins = props.termins.filter(
      (termin: any) => termin.department_id == form.value.department_id
    );
    terminList.value = filteredTermins;
  } else {
    // If no department selected but PO type is Lainnya, show empty list
    terminList.value = [];
  }
}

// Initialize harga field with grand total if it's a Reguler PO
onMounted(async () => {
  if (form.value.tipe_po === "Reguler" || form.value.tipe_po === "Lainnya") {
    // Small delay to ensure the barang grid component is fully mounted
    setTimeout(() => {
      if (
        barangGridRef.value?.grandTotal &&
        typeof barangGridRef.value.grandTotal === "number"
      ) {
        form.value.harga = barangGridRef.value.grandTotal;
      }
    }, 200);
  }

  // Initialize termin list if PO type is Lainnya and department is selected
  if (form.value.tipe_po === "Lainnya" && form.value.department_id) {
    try {
      const response = await axios.get("/purchase-orders/termins/by-department", {
        params: { department_id: form.value.department_id },
      });
      if (response.data && response.data.success) {
        terminList.value = response.data.data || [];
      }
    } catch (error) {
      console.error("Error fetching termins by department:", error);
      // Fallback to filtering from props
      const filteredTermins = props.termins.filter(
        (termin: any) => termin.department_id == form.value.department_id
      );
      terminList.value = filteredTermins;
    }
  }
});

// Keep tanggal as Date internally; display uses displayTanggal

// Display read-only tanggal in dd-MM-yyyy
const displayTanggal = computed(() => {
  try {
    return format(new Date(form.value.tanggal as any), "dd-MM-yyyy");
  } catch {
    return "";
  }
});

const validTanggalGiro = computed({
  get: () => {
    if (!form.value.tanggal_giro) return null as any;
    try {
      const date = new Date(form.value.tanggal_giro as any);
      return isNaN(date.getTime()) ? null : (date as any);
    } catch {
      return null as any;
    }
  },
  set: (value) => {
    form.value.tanggal_giro = value as any;
  },
});

const validTanggalCair = computed({
  get: () => {
    if (!form.value.tanggal_cair) return null as any;
    try {
      const date = new Date(form.value.tanggal_cair as any);
      return isNaN(date.getTime()) ? null : (date as any);
    } catch {
      return null as any;
    }
  },
  set: (value) => {
    form.value.tanggal_cair = value as any;
  },
});

// Formatted numeric inputs (thousand separators + decimals, no currency symbol)
const displayHarga = computed<string>({
  get: () => {
    // Always render from form.harga to avoid circular dependency with child grandTotal
    return formatCurrency(form.value.harga ?? "");
  },
  set: (val: string) => {
    const parsed = parseCurrency(val);
    form.value.harga = parsed === "" ? null : Number(parsed);
  },
});

// Handler functions for supplier and bank selection
async function handleSupplierChange(supplierId: string) {
  form.value.supplier_id = supplierId;
  form.value.bank_id = "";
  form.value.nama_rekening = "";
  form.value.no_rekening = "";
  selectedSupplierBankAccounts.value = [];
  selectedSupplier.value = null;

  if (!supplierId) return;

  try {
    const response = await axios.post("/purchase-orders/supplier-bank-accounts", {
      supplier_id: supplierId,
    });

    const { supplier, bank_accounts } = response.data;
    selectedSupplier.value = supplier;
    selectedSupplierBankAccounts.value = bank_accounts;

    // Auto-select bank and fill details if only one bank account
    if (bank_accounts.length === 1) {
      const account = bank_accounts[0];
      form.value.bank_id = String(account.bank_id);
      form.value.nama_rekening = account.nama_rekening;
      form.value.no_rekening = account.no_rekening;
    }
  } catch (error) {
    console.error("Error fetching supplier bank accounts:", error);
    addError("Gagal mengambil data rekening supplier");
  }
}

function handleBankChange(bankId: string) {
  form.value.bank_id = bankId;
  form.value.nama_rekening = "";
  form.value.no_rekening = "";

  if (!bankId) return;

  const selectedAccount = selectedSupplierBankAccounts.value.find(
    (account: any) => String(account.bank_id) === bankId
  );

  if (selectedAccount) {
    form.value.nama_rekening = selectedAccount.nama_rekening;
    form.value.no_rekening = selectedAccount.no_rekening;
  }
}

function handleSelectCreditCard(creditCardId: string) {
  selectedCreditCardId.value = creditCardId;
  form.value.no_kartu_kredit = "";
  form.value.bank_id = "";
  selectedCreditCardBankName.value = "";
  if (!creditCardId) return;
  const cc = creditCardOptions.value.find(
    (c: any) => String(c.id) === String(creditCardId)
  );
  if (cc) {
    form.value.no_kartu_kredit = cc.no_kartu_kredit || "";
    form.value.bank_id = cc.bank_id ? String(cc.bank_id) : "";
    selectedCreditCardBankName.value = cc.bank?.nama_bank
      ? cc.bank.singkatan
        ? `${cc.bank.nama_bank} (${cc.bank.singkatan})`
        : cc.bank.nama_bank
      : "";
  }
}

function searchCreditCards(query: string) {
  clearTimeout(creditCardSearchTimeout);
  creditCardSearchTimeout = setTimeout(async () => {
    if (!form.value.department_id || form.value.metode_pembayaran !== "Kredit") return;
    try {
      const { data } = await axios.get("/credit-cards", {
        headers: { Accept: "application/json" },
        params: {
          department_id: form.value.department_id,
          status: "active",
          search: query,
          per_page: 50,
        },
      });
      creditCardOptions.value = Array.isArray(data?.data) ? data.data : [];
    } catch {
      // ignore
    }
  }, 300);
}

// Customer functions for Refund Konsumen
function handleCustomerChange(customerId: string) {
  form.value.customer_id = customerId;
  // Clear customer bank and account fields when customer changes
  form.value.customer_bank_id = "";
  form.value.customer_nama_rekening = "";
  form.value.customer_no_rekening = "";
}

function handleCustomerBankChange(bankId: string) {
  form.value.customer_bank_id = bankId;
}

function searchCustomers(query: string) {
  clearTimeout(customerSearchTimeout);
  customerSearchTimeout = setTimeout(async () => {
    if (!form.value.department_id) return;
    try {
      const { data } = await axios.get("/purchase-orders/ar-partners", {
        headers: { Accept: "application/json" },
        params: {
          department_id: form.value.department_id,
          search: query,
          limit: 50,
        },
      });
      customerOptions.value = Array.isArray(data?.data) ? data.data : [];
    } catch {
      // ignore
    }
  }, 300);
}

// Removed auto-sync from cicilan to nominal; cicilan is a standalone manual input

function onAddPph(pphBaru: any) {
  // Transform the new PPH data to match the expected format
  const transformedPph = {
    id: pphBaru.id || pphBaru.kode, // Use kode as fallback if id is not available
    kode: pphBaru.kode,
    nama: pphBaru.nama,
    tarif: pphBaru.tarif,
  };
  pphList.value.push(transformedPph);

  // Refresh PPH list dari backend untuk memastikan data terbaru
  // Note: Ini akan di-handle oleh parent component yang memanggil PurchaseOrderBarangGrid
}
function goBack() {
  router.visit("/purchase-orders");
}

function handlePerihalCreated(newItem: any) {
  if (newItem && newItem.id) {
    perihalList.value.push({
      id: newItem.id,
      nama: newItem.nama,
      status: newItem.status,
    });
    form.value.perihal_id = String(newItem.id);
  }
}

function handleTerminCreated(newItem: any) {
  if (newItem && newItem.id) {
    terminList.value.push({
      id: newItem.id,
      no_referensi: newItem.no_referensi,
      jumlah_termin: newItem.jumlah_termin,
    });
    // Set selected termin to the newly created one
    form.value.termin_id = String(newItem.id);
    // Immediately fetch and refresh termin info and barang list
    handleTerminChange(String(newItem.id));
  }
}

function handleTerminChange(terminId: string) {
  form.value.termin_id = terminId;
  // Clear barang list when termin changes
  barangList.value = [];
}

// Search termins (debounced) similar to BankMasuk customer search
let terminSearchTimeout: ReturnType<typeof setTimeout>;
function searchTermins(query: string) {
  clearTimeout(terminSearchTimeout);
  terminSearchTimeout = setTimeout(async () => {
    try {
      // If department is selected, search within that department
      if (form.value.department_id && form.value.tipe_po === "Lainnya") {
        const { data } = await axios.get("/purchase-orders/termins/by-department", {
          params: {
            department_id: form.value.department_id,
            search: query,
          },
        });
        if (data && data.success) {
          terminList.value = data.data || [];
        }
      } else {
        // Fallback to general search if no department selected
        const { data } = await axios.get("/purchase-orders/termins/search", {
          params: { search: query, per_page: 20 },
        });
        if (data && data.success) {
          terminList.value = data.data || [];
        }
      }
    } catch (e) {
      console.error("Error searching termins:", e);
    }
  }, 300);
}

function validateForm() {
  errors.value = {};
  let isValid = true;

  if (form.value.tipe_po === "Reguler") {
    // Validasi field wajib untuk tipe Reguler
    if (!form.value.department_id) {
      errors.value.department_id = "Departemen wajib dipilih";
      isValid = false;
    }
    if (!form.value.perihal_id) {
      errors.value.perihal_id = "Perihal wajib dipilih";
      isValid = false;
    }

    // Check if it's Refund Konsumen perihal
    const isRefundKonsumen =
      selectedPerihalName.value?.toLowerCase() ===
      "permintaan pembayaran refund konsumen";

    if (form.value.metode_pembayaran === "Transfer") {
      if (isRefundKonsumen) {
        // For Refund Konsumen, validate customer fields
        if (!form.value.customer_id) {
          errors.value.customer_id = "Customer wajib dipilih untuk metode Transfer";
          isValid = false;
        }
        if (!form.value.customer_bank_id) {
          errors.value.customer_bank_id = "Nama Bank wajib dipilih";
          isValid = false;
        }
        if (!form.value.customer_nama_rekening) {
          errors.value.customer_nama_rekening = "Nama Rekening wajib diisi";
          isValid = false;
        }
        if (!form.value.customer_no_rekening) {
          errors.value.customer_no_rekening = "No. Rekening wajib diisi";
          isValid = false;
        }
      } else {
        // For other perihals, validate supplier fields
        if (!form.value.supplier_id) {
          errors.value.supplier_id = "Supplier wajib dipilih untuk metode Transfer";
          isValid = false;
        }
      }
    }
    // No Invoice is optional
    if (!form.value.harga) {
      // Auto-populate harga from grand total if available
      if (
        barangGridRef.value?.grandTotal &&
        typeof barangGridRef.value.grandTotal === "number"
      ) {
        form.value.harga = barangGridRef.value.grandTotal;
      } else {
        errors.value.harga = "Harga wajib diisi";
        isValid = false;
      }
    }
    if (!form.value.metode_pembayaran) {
      errors.value.metode_pembayaran = "Metode pembayaran wajib dipilih";
      isValid = false;
    }

    // Validasi field berdasarkan metode pembayaran
    if (form.value.metode_pembayaran === "Cek/Giro") {
      if (!form.value.no_giro) {
        errors.value.no_giro = "No. Cek/Giro wajib diisi";
        isValid = false;
      }
      if (!form.value.tanggal_giro) {
        errors.value.tanggal_giro = "Tanggal Giro wajib diisi";
        isValid = false;
      }
      if (!form.value.tanggal_cair) {
        errors.value.tanggal_cair = "Tanggal Cair wajib diisi";
        isValid = false;
      }
    } else if (
      form.value.metode_pembayaran === "Transfer" ||
      !form.value.metode_pembayaran
    ) {
      // Validasi field bank untuk Transfer atau ketika belum memilih metode pembayaran
      if (isRefundKonsumen) {
        // For Refund Konsumen, validate customer bank fields (already validated above)
        // No additional validation needed here
      } else {
        // For other perihals, validate supplier bank fields
        if (!form.value.bank_id) {
          errors.value.bank_id = "Nama Bank wajib dipilih";
          isValid = false;
        }
        if (!form.value.nama_rekening) {
          errors.value.nama_rekening = "Nama Rekening wajib diisi";
          isValid = false;
        }
        if (!form.value.no_rekening) {
          errors.value.no_rekening = "No. Rekening/VA wajib diisi";
          isValid = false;
        }
      }
    } else if (form.value.metode_pembayaran === "Kredit") {
      if (!form.value.no_kartu_kredit) {
        errors.value.no_kartu_kredit = "No. Kartu Kredit wajib diisi";
        isValid = false;
      }
    }
  } else if (form.value.tipe_po === "Lainnya") {
    if (!form.value.department_id) {
      errors.value.department_id = "Departemen wajib dipilih";
      isValid = false;
    }
    if (!form.value.perihal_id) {
      errors.value.perihal_id = "Perihal wajib dipilih";
      isValid = false;
    }
    if (!form.value.termin_id) {
      errors.value.termin_id = "No Ref Termin wajib dipilih";
      isValid = false;
    }
  }

  if (!barangList.value.length) {
    errors.value.barang = "Minimal 1 barang harus diisi";
    isValid = false;
  }

  // Validate file upload for staff toko & kepala toko (hanya untuk tipe Reguler)
  if (isStaffToko.value && form.value.tipe_po === "Reguler") {
    if (!dokumenFile.value) {
      errors.value.dokumen = "Draft Invoice harus diupload";
      isValid = false;
    } else {
      // Validate file type
      const allowedTypes = ["image/jpeg", "image/jpg", "image/png", "application/pdf"];
      const fileType = dokumenFile.value.type;
      if (!allowedTypes.includes(fileType)) {
        errors.value.dokumen =
          "Format file tidak didukung. Hanya file JPG, JPEG, PNG, dan PDF yang diperbolehkan";
        isValid = false;
      }

      // Validate file size (50MB)
      const maxSize = 50 * 1024 * 1024; // 50MB in bytes
      if (dokumenFile.value.size > maxSize) {
        errors.value.dokumen = "Ukuran file terlalu besar. Maksimal 50 MB";
        isValid = false;
      }
    }
  }

  return isValid;
}

// Validasi khusus untuk draft - hanya Departemen, Tanggal, dan Tipe
function validateDraftForm() {
  errors.value = {};
  let isValid = true;

  // Validasi Departemen
  if (!form.value.department_id) {
    errors.value.department_id = "Departemen wajib dipilih";
    isValid = false;
  }

  // Validasi Tanggal
  if (!form.value.tanggal) {
    errors.value.tanggal = "Tanggal wajib dipilih";
    isValid = false;
  }

  // Validasi Tipe PO
  if (!form.value.tipe_po) {
    errors.value.tipe_po = "Tipe PO wajib dipilih";
    isValid = false;
  }

  // Semua field lain tidak wajib untuk draft
  return isValid;
}

async function onSaveDraft() {
  clearAll();
  // Untuk draft, tidak perlu validasi form lengkap
  // Hanya validasi minimal yang diperlukan
  if (!validateDraftForm()) return;
  loading.value = true;
  // Reset diskon dan pph_id jika tidak aktif
  if (
    !form.value.diskon ||
    form.value.diskon === null ||
    form.value.diskon === undefined
  ) {
    form.value.diskon = 0;
  }
  if (
    !form.value.pph_id ||
    (Array.isArray(form.value.pph_id) && form.value.pph_id.length === 0)
  ) {
    form.value.pph_id = [];
  }
  try {
    const formData = new FormData();
    const fieldsToFormat = ["tanggal", "tanggal_giro", "tanggal_cair"];

    // Only submit fields that have values or are required
    const fieldsToSubmit: any = {
      tipe_po: form.value.tipe_po,
      tanggal: form.value.tanggal,
      department_id: form.value.department_id,
      perihal_id: form.value.perihal_id,
      supplier_id: form.value.supplier_id,
      no_invoice: form.value.no_invoice,
      harga: form.value.harga,
      detail_keperluan: form.value.detail_keperluan,
      metode_pembayaran: form.value.metode_pembayaran,
      keterangan: form.value.note || form.value.keterangan, // Map note to keterangan
      diskon: form.value.diskon,
      ppn: form.value.ppn,
      pph_id: form.value.pph_id,
      termin: form.value.termin,
      // nominal is intentionally not sent; cicilan is the manual value
    };

    // Add bank-related fields if Transfer or no method selected
    if (form.value.metode_pembayaran === "Transfer" || !form.value.metode_pembayaran) {
      // Check if it's Refund Konsumen perihal
      const isRefundKonsumen =
        selectedPerihalName.value?.toLowerCase() ===
        "permintaan pembayaran refund konsumen";

      if (isRefundKonsumen) {
        // For Refund Konsumen, submit customer fields
        fieldsToSubmit.customer_id = form.value.customer_id;
        fieldsToSubmit.customer_bank_id = form.value.customer_bank_id;
        fieldsToSubmit.customer_nama_rekening = form.value.customer_nama_rekening;
        fieldsToSubmit.customer_no_rekening = form.value.customer_no_rekening;
      } else {
        // For other perihals, submit supplier fields
        fieldsToSubmit.bank_id = form.value.bank_id;
        fieldsToSubmit.nama_rekening = form.value.nama_rekening;
        fieldsToSubmit.no_rekening = form.value.no_rekening;
      }
    }

    // Add Cek/Giro fields if Cek/Giro method selected
    if (form.value.metode_pembayaran === "Cek/Giro") {
      fieldsToSubmit.no_giro = form.value.no_giro;
      fieldsToSubmit.tanggal_giro = form.value.tanggal_giro;
      fieldsToSubmit.tanggal_cair = form.value.tanggal_cair;
    }

    // Add Kredit fields if Kredit method selected
    if (form.value.metode_pembayaran === "Kredit") {
      fieldsToSubmit.no_kartu_kredit = form.value.no_kartu_kredit;
    }

    Object.entries(fieldsToSubmit).forEach(([k, v]) => {
      let value: any = v as any;
      if (fieldsToFormat.includes(k)) {
        value = formatDateForSubmit(value);
      }

      // Handle special field types
      if (k === "ppn") {
        // Convert boolean to 1 or 0 for server
        value = value ? 1 : 0;
      } else if (k === "pph_id") {
        // Handle PPH ID - extract the ID from the array or use the value directly
        if (Array.isArray(value) && value.length > 0) {
          // Extract just the ID from the array
          const pphId = value[0];
          if (pphId) {
            value = pphId; // Send just the ID value
          } else {
            value = null;
          }
        } else {
          value = null; // Don't send empty array
        }
      }

      if (value !== null && value !== undefined && value !== "") {
        formData.append(k, value);
      }
    });

    // Add termin_id for Lainnya type (only when present)
    if (form.value.tipe_po === "Lainnya" && form.value.termin_id != null) {
      formData.append("termin_id", form.value.termin_id);
    }

    formData.append("status", "Draft");
    formData.append("barang", JSON.stringify(barangList.value));
    if (dokumenFile.value) formData.append("dokumen", dokumenFile.value);
    await axios.post("/purchase-orders", formData, {
      headers: { "Content-Type": "multipart/form-data", Accept: "application/json" },
    });
    addSuccess("Draft PO berhasil disimpan!");
    // Clear the temporary draft storage
    if (barangGridRef.value?.clearDraftStorage) {
      barangGridRef.value.clearDraftStorage();
    }
    // Ensure loading is turned off before navigating to avoid perceived freeze
    loading.value = false;
    // Use hard navigation to avoid any SPA state issues
    window.location.assign("/purchase-orders");
  } catch (e: any) {
    if (e?.response?.data?.errors) {
      errors.value = e.response.data.errors;

      // Tampilkan pesan error utama di message panel
      if (e?.response?.data?.message) {
        addError(e.response.data.message);
      }

      // Tampilkan detail error untuk field tertentu
      if (e?.response?.data?.error_messages) {
        Object.values(e.response.data.error_messages).forEach((message: any) => {
          addError(message);
        });
      }
    } else {
      addError(e?.response?.data?.message || "Gagal simpan draft.");
    }
  } finally {
    // keep as safety; no-op if already set to false
    loading.value = false;
  }
}

function showSubmitConfirmation() {
  confirmAction.value = "submit";
  showConfirmDialog.value = true;
}

async function onSubmit() {
  clearAll();
  if (!validateForm()) {
    // Tutup pop up konfirmasi jika validasi frontend gagal
    showConfirmDialog.value = false;

    // Tampilkan pesan error di message panel
    addError("Validasi form gagal. Silakan periksa kembali data yang diisi.");
    return;
  }
  loading.value = true;
  // Reset diskon dan pph_id jika tidak aktif
  if (
    !form.value.diskon ||
    form.value.diskon === null ||
    form.value.diskon === undefined
  ) {
    form.value.diskon = 0;
  }
  if (
    !form.value.pph_id ||
    (Array.isArray(form.value.pph_id) && form.value.pph_id.length === 0)
  ) {
    form.value.pph_id = [];
  }
  try {
    const formData = new FormData();
    const fieldsToFormat = ["tanggal", "tanggal_giro", "tanggal_cair"];

    // Only submit fields that have values or are required
    const fieldsToSubmit: any = {
      tipe_po: form.value.tipe_po,
      tanggal: form.value.tanggal,
      department_id: form.value.department_id,
      perihal_id: form.value.perihal_id,
      supplier_id: form.value.supplier_id,
      no_invoice: form.value.no_invoice,
      harga: form.value.harga,
      detail_keperluan: form.value.detail_keperluan,
      metode_pembayaran: form.value.metode_pembayaran,
      keterangan: form.value.note || form.value.keterangan, // Map note to keterangan
      diskon: form.value.diskon,
      ppn: form.value.ppn,
      pph_id: form.value.pph_id,
      termin: form.value.termin,
      // nominal is intentionally not sent; cicilan is the manual value
    };

    // Add bank-related fields if Transfer or no method selected
    if (form.value.metode_pembayaran === "Transfer" || !form.value.metode_pembayaran) {
      // Check if it's Refund Konsumen perihal
      const isRefundKonsumen =
        selectedPerihalName.value?.toLowerCase() ===
        "permintaan pembayaran refund konsumen";

      if (isRefundKonsumen) {
        // For Refund Konsumen, submit customer fields
        fieldsToSubmit.customer_id = form.value.customer_id;
        fieldsToSubmit.customer_bank_id = form.value.customer_bank_id;
        fieldsToSubmit.customer_nama_rekening = form.value.customer_nama_rekening;
        fieldsToSubmit.customer_no_rekening = form.value.customer_no_rekening;
      } else {
        // For other perihals, submit supplier fields
        fieldsToSubmit.bank_id = form.value.bank_id;
        fieldsToSubmit.nama_rekening = form.value.nama_rekening;
        fieldsToSubmit.no_rekening = form.value.no_rekening;
      }
    }

    // Add Cek/Giro fields if Cek/Giro method selected
    if (form.value.metode_pembayaran === "Cek/Giro") {
      fieldsToSubmit.no_giro = form.value.no_giro;
      fieldsToSubmit.tanggal_giro = form.value.tanggal_giro;
      fieldsToSubmit.tanggal_cair = form.value.tanggal_cair;
    }

    // Add Kredit fields if Kredit method selected
    if (form.value.metode_pembayaran === "Kredit") {
      fieldsToSubmit.no_kartu_kredit = form.value.no_kartu_kredit;
    }

    Object.entries(fieldsToSubmit).forEach(([k, v]) => {
      let value: any = v as any;
      if (fieldsToFormat.includes(k)) {
        value = formatDateForSubmit(value);
      }

      // Handle special field types
      if (k === "ppn") {
        // Convert boolean to 1 or 0 for server
        value = value ? 1 : 0;
      } else if (k === "pph_id") {
        // Handle PPH ID - extract the ID from the array or use the value directly
        if (Array.isArray(value) && value.length > 0) {
          // Extract just the ID from the array
          const pphId = value[0];
          if (pphId) {
            value = pphId; // Send just the ID value
          } else {
            value = null;
          }
        } else {
          value = null; // Don't send empty array
        }
      }

      if (value !== null && value !== undefined && value !== "") {
        formData.append(k, value);
      }
    });

    // If Kredit, create as Approved immediately; otherwise create as In Progress
    const isKredit = form.value.metode_pembayaran === "Kredit";
    // Add termin_id for Lainnya type
    if (form.value.tipe_po === "Lainnya") {
      formData.append("termin_id", form.value.termin_id);
    }

    formData.append("status", isKredit ? "Approved" : "In Progress");
    formData.append("barang", JSON.stringify(barangList.value));
    if (dokumenFile.value) formData.append("dokumen", dokumenFile.value);

    await axios.post("/purchase-orders", formData, {
      headers: { "Content-Type": "multipart/form-data", Accept: "application/json" },
    });

    if (isKredit) {
      addSuccess("PO Kredit berhasil disetujui!");
    } else {
      addSuccess("PO berhasil dikirim!");
    }

    // Clear the temporary draft storage
    if (barangGridRef.value?.clearDraftStorage) {
      barangGridRef.value.clearDraftStorage();
    }
    setTimeout(() => router.visit("/purchase-orders"), 800);
  } catch (e: any) {
    if (e?.response?.data?.errors) {
      errors.value = e.response.data.errors;

      // Tampilkan pesan error utama di message panel
      if (e?.response?.data?.message) {
        addError(e.response.data.message);
      }

      // Tampilkan detail error untuk field tertentu
      if (e?.response?.data?.error_messages) {
        Object.values(e.response.data.error_messages).forEach((message: any) => {
          addError(message);
        });
      }

      // Tutup pop up konfirmasi jika ada error
      showConfirmDialog.value = false;
    } else {
      addError(e?.response?.data?.message || "Gagal kirim PO.");
      // Tutup pop up konfirmasi jika ada error
      showConfirmDialog.value = false;
    }
  } finally {
    loading.value = false;
  }
}

function formatDateForSubmit(value: any) {
  if (!value) return "";
  const date = value instanceof Date ? value : new Date(value);
  if (isNaN(date.getTime())) return "";
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, "0");
  const day = String(date.getDate()).padStart(2, "0");
  return `${year}-${month}-${day}`;
}

// Force re-render of date pickers to prevent display issues
const datePickerKey = ref(0);

watch(
  () => form.value.tanggal_cair,
  () => {
    // Force re-render of both date pickers when any date changes
    nextTick(() => {
      datePickerKey.value++;
    });
  }
);

watch(
  () => form.value.tanggal_giro,
  () => {
    // Force re-render of both date pickers when any date changes
    nextTick(() => {
      datePickerKey.value++;
    });
  }
);
</script>

<style scoped>
.floating-input {
  position: relative;
}

.floating-input-field {
  width: 100%;
  padding: 1rem 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 0.375rem;
  font-size: 0.875rem;
  line-height: 1.25rem;
  background-color: white;
  transition: all 0.3s ease-in-out;
}

.floating-input-field:focus {
  outline: none;
  border-color: #1f9254;
  box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
}

.floating-label {
  position: absolute;
  left: 0.75rem;
  top: 1rem;
  font-size: 0.875rem;
  line-height: 1.25rem;
  color: #9ca3af;
  transition: all 0.3s ease-in-out;
  pointer-events: none;
  transform-origin: left top;
  background-color: white;
  padding: 0 0.25rem;
  z-index: 1;
}

/* When input is focused or has value - label goes to border */
.floating-input-field:focus ~ .floating-label,
.floating-input-field:not(:placeholder-shown) ~ .floating-label {
  top: -0.5rem;
  left: 0.75rem;
  font-size: 0.75rem;
  line-height: 1rem;
  color: #333333;
  transform: translateY(0) scale(1);
}

/* Special handling for select - check if it has selected value */
.floating-input select.floating-input-field:not([value=""]) ~ .floating-label,
.floating-input select.floating-input-field:focus ~ .floating-label {
  top: -0.5rem;
  left: 0.75rem;
  font-size: 0.75rem;
  line-height: 1rem;
  color: #333333;
  transform: translateY(0) scale(1);
}

/* Textarea specific styles */
.floating-input-field:is(textarea) {
  resize: vertical;
  padding-top: 1rem;
  padding-bottom: 1rem;
}

.floating-input-field:is(textarea):focus ~ .floating-label,
.floating-input-field:is(textarea):not(:placeholder-shown) ~ .floating-label {
  top: -0.5rem;
}

/* Hover effects */
.floating-input:hover .floating-input-field {
  border-color: #9ca3af;
}

.floating-input:hover .floating-input-field:focus {
  border-color: #1f9254;
}

/* Make sure the label background covers the border */
.floating-input-field:focus ~ .floating-label,
.floating-input-field:not(:placeholder-shown) ~ .floating-label,
.floating-input select.floating-input-field:not([value=""]) ~ .floating-label,
.floating-input select.floating-input-field:focus ~ .floating-label {
  background-color: white;
  padding: 0 0.25rem;
}

/* Disabled field styling */
.floating-input-field:disabled {
  background-color: #f3f4f6;
  color: #6b7280;
  cursor: not-allowed;
  opacity: 0.7;
}

.floating-input-field:disabled ~ .floating-label {
  color: #9ca3af;
}
</style>

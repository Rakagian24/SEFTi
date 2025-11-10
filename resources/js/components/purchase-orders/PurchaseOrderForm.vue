<template>
  <div class="bg-white rounded-lg shadow-sm p-6">
    <form @submit.prevent="onSubmit" novalidate class="space-y-4">
      <!-- Form Layout -->
      <div class="space-y-4">
        <!-- Row 1: No. PO | Metode Pembayaran -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div class="floating-input">
            <div class="floating-input-field bg-gray-50 text-gray-600 cursor-not-allowed">
              {{ form.no_po || "Akan di-generate otomatis" }}
            </div>
            <label for="no_po" class="floating-label">No. Purchase Order</label>
          </div>
          <div>
            <CustomSelect
              :model-value="form.metode_pembayaran ?? ''"
              @update:modelValue="(val) => (form.metode_pembayaran = val as string)"
              :options="[
                { label: 'Transfer', value: 'Transfer' },
                /*{ label: 'Cek/Giro', value: 'Cek/Giro' },*/
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
              Form ini wajib di isi
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
          <div v-if="form.metode_pembayaran === 'Transfer' || !form.metode_pembayaran">
            <!-- Customer selection for Refund Konsumen -->
            <div v-if="isRefundKonsumenPerihal">
              <CustomSelect
                :model-value="form.customer_id ?? ''"
                @update:modelValue="(val) => handleCustomerChange(val as string)"
                :options="(Array.isArray(customerOptions) ? customerOptions : []).map((c: any) => ({ label: c.nama_ap, value: String(c.id) }))"
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
                Form ini wajib di isi
              </div>
            </div>
            <!-- Supplier selection for other cases -->
            <div v-else>
              <CustomSelect
                :model-value="form.supplier_id ?? ''"
                @update:modelValue="(val) => handleSupplierChange(val as string)"
                :options="(Array.isArray(supplierList) ? supplierList : []).map((s: any) => ({ label: s.nama_supplier, value: String(s.id) }))"
                :searchable="true"
                :disabled="!form.department_id"
                @search="searchSuppliers"
                placeholder="Pilih Supplier"
                :class="{ 'border-red-500': errors.supplier_id }"
              >
                <template #label>
                  Nama Supplier<span class="text-red-500">*</span>
                </template>
              </CustomSelect>
              <div v-if="errors.supplier_id" class="text-red-500 text-xs mt-1">
                Form ini wajib di isi
              </div>
            </div>
          </div>
          <div v-else-if="form.metode_pembayaran === 'Cek/Giro'" class="floating-input">
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
              Form ini wajib di isi
            </div>
          </div>
          <div v-else-if="form.metode_pembayaran === 'Kredit'">
            <CustomSelect
              :model-value="selectedCreditCardId ?? ''"
              @update:modelValue="(val) => handleSelectCreditCard(val as string)"
              :options="(Array.isArray(creditCardOptions) ? creditCardOptions : []).map((cc: any) => ({ label: cc.nama_pemilik, value: String(cc.id) }))"
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
              Form ini wajib di isi
            </div>
          </div>
        </div>

        <!-- Row 3: Tanggal | Nama Bank / Tanggal Giro -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div class="floating-input">
            <input
              type="text"
              :value="displayTanggal"
              id="tanggal"
              class="floating-input-field bg-gray-50 text-gray-600 cursor-not-allowed"
              placeholder=" "
              readonly
            />
            <label for="tanggal" class="floating-label">Tanggal</label>
          </div>
          <!-- Dynamic field based on payment method -->
          <div v-if="form.metode_pembayaran === 'Transfer' || !form.metode_pembayaran">
            <!-- Customer bank fields for Refund Konsumen -->
            <div v-if="isRefundKonsumenPerihal">
              <CustomSelect
                :model-value="form.customer_bank_id ?? ''"
                @update:modelValue="(val) => handleCustomerBankChange(val as string)"
                :options="(Array.isArray(bankList) ? bankList : []).map((bank: any) => ({
                  label: `${bank.nama_bank} (${bank.singkatan})`,
                  value: String(bank.id)
                }))"
                placeholder="Pilih Bank"
                :class="{ 'border-red-500': errors.customer_bank_id }"
              >
                <template #label> Nama Bank<span class="text-red-500">*</span> </template>
              </CustomSelect>
              <div v-if="errors.customer_bank_id" class="text-red-500 text-xs mt-1">
                Form ini wajib di isi
              </div>
            </div>
            <!-- Supplier bank selection for other cases -->
            <div v-else>
              <CustomSelect
                :model-value="form.bank_supplier_account_id ?? ''"
                @update:modelValue="(val) => handleBankSupplierAccountChange(val as string)"
                :options="(Array.isArray(selectedSupplierBankAccounts) ? selectedSupplierBankAccounts : []).map((account: any) => ({
                  label: `${account.nama_rekening}`,
                  value: String(account.id)
                }))"
                placeholder="Pilih Rekening"
                :disabled="selectedSupplierBankAccounts.length === 0"
                :class="{ 'border-red-500': errors.bank_supplier_account_id }"
              >
                <template #label>
                  Nama Rekening<span class="text-red-500">*</span>
                </template>
              </CustomSelect>
              <div
                v-if="errors.bank_supplier_account_id"
                class="text-red-500 text-xs mt-1"
              >
                Form ini wajib di isi
              </div>
            </div>
          </div>
          <div v-else-if="form.metode_pembayaran === 'Cek/Giro'" class="floating-input">
            <label class="block text-xs font-light text-gray-700 mb-1"
              >Tanggal Giro<span class="text-red-500">*</span></label
            >
            <Datepicker
              :model-value="validTanggalGiro"
              @update:modelValue="emit('update:validTanggalGiro', $event as any)"
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
              Form ini wajib di isi
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
            <label class="floating-label" for="nama_bank_kredit">Nama Bank</label>
          </div>
        </div>

        <!-- Row 4: Departemen | Nama Rekening (Refund) / No Rekening (Supplier) / Tanggal Cair -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <CustomSelect
              :model-value="form.department_id ?? ''"
              @update:modelValue="(val) => (form.department_id = val as any)"
              :options="(Array.isArray(departemenList) ? departemenList : []).map((d: any) => ({ label: d.name, value: String(d.id) }))"
              :disabled="(departemenList || []).length === 1"
              placeholder="Pilih Departemen"
              :class="{ 'border-red-500': errors.department_id }"
            >
              <template #label> Departemen<span class="text-red-500">*</span> </template>
            </CustomSelect>
            <div v-if="errors.department_id" class="text-red-500 text-xs mt-1">
              Form ini wajib di isi
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
              <div v-if="errors.customer_nama_rekening" class="text-red-500 text-xs mt-1">
                Form ini wajib di isi
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
                Form ini wajib di isi
              </div>
            </div>
          </div>
          <div v-else-if="form.metode_pembayaran === 'Cek/Giro'" class="floating-input">
            <label class="block text-xs font-light text-gray-700 mb-1"
              >Tanggal Cair<span class="text-red-500">*</span></label
            >
            <Datepicker
              :model-value="validTanggalCair"
              @update:modelValue="emit('update:validTanggalCair', $event as any)"
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
              Form ini wajib di isi
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
              Form ini wajib di isi
            </div>
          </div>
        </div>

        <!-- Row 5: Perihal | No Rekening (Refund Konsumen) -->
        <div v-if="isRefundKonsumenPerihal" class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <CustomSelect
              :model-value="form.perihal_id ?? ''"
              @update:modelValue="(val) => (form.perihal_id = val as any)"
              :options="(Array.isArray(perihalList) ? perihalList : []).map((p: any) => ({ label: p.nama, value: String(p.id) }))"
              placeholder="Pilih Perihal"
              :class="{ 'border-red-500': errors.perihal_id }"
            >
              <template #label> Perihal<span class="text-red-500">*</span> </template>
              <template #suffix>
                <span
                  class="inline-flex items-center justify-center w-6 h-6 rounded-md text-white bg-blue-500 hover:bg-blue-600 focus:outline-none"
                  @click.stop="$emit('showAddPerihalModal')"
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
              Form ini wajib di isi
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
              Form ini wajib di isi
            </div>
          </div>
        </div>

        <!-- Row 5: Perihal | Note (for non-Refund Konsumen) -->
        <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <CustomSelect
              :model-value="form.perihal_id ?? ''"
              @update:modelValue="(val) => (form.perihal_id = val as any)"
              :options="(Array.isArray(perihalList) ? perihalList : []).map((p: any) => ({ label: p.nama, value: String(p.id) }))"
              placeholder="Pilih Perihal"
              :class="{ 'border-red-500': errors.perihal_id }"
            >
              <template #label> Perihal<span class="text-red-500">*</span> </template>
              <template #suffix>
                <span
                  class="inline-flex items-center justify-center w-6 h-6 rounded-md text-white bg-blue-500 hover:bg-blue-600 focus:outline-none"
                  @click.stop="$emit('showAddPerihalModal')"
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
              Form ini wajib di isi
            </div>

            <!-- Jenis Barang (show only for HG/Zi&Glo + PO Reguler + Perihal: Permintaan Pembayaran Barang) -->
            <div v-if="form.tipe_po === 'Reguler' && selectedPerihalName?.toLowerCase() === 'permintaan pembayaran barang' && isHGOrZiGlo" class="mt-4">
              <CustomSelect
                :model-value="form.jenis_barang_id ?? ''"
                @update:modelValue="(val) => (form.jenis_barang_id = val as any)"
                :options="(Array.isArray(jenisBarangList) ? jenisBarangList : []).map((j: any) => ({ label: j.singkatan ? `${j.nama_jenis_barang} (${j.singkatan})` : j.nama_jenis_barang, value: String(j.id) }))"
                :searchable="true"
                @search="searchJenisBarangs"
                placeholder="Pilih Jenis Barang"
              >
                <template #label>
                  Jenis Barang
                </template>
              </CustomSelect>
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
          <!-- No Invoice for Reguler -->
          <div v-if="form.tipe_po === 'Reguler'" class="floating-input">
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
              Form ini wajib di isi
            </div>
          </div>

          <!-- No Ref Termin for Lainnya -->
          <div v-if="form.tipe_po === 'Lainnya'">
            <div class="space-y-2">
              <CustomSelect
                :model-value="form.termin_id ?? ''"
                @update:modelValue="(val) => handleTerminChange(val as any)"
                :options="terminOptions"
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
                    @click.stop="$emit('showAddTerminModal')"
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

              <div v-if="errors.termin_id" class="text-red-500 text-xs mt-1">
                Form ini wajib di isi
              </div>
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
          <!-- Harga untuk Reguler -->
          <div v-if="form.tipe_po === 'Reguler'" class="floating-input">
            <input
              type="text"
              :value="displayHarga"
              id="harga"
              class="floating-input-field"
              :class="{ 'border-red-500': errors.harga }"
              placeholder=" "
              required
              :readonly="!isSpecialPerihal"
              inputmode="decimal"
              @keydown="allowNumericKeydown"
              @input="onHargaInput"
            />
            <label for="harga" class="floating-label">
              {{ isRefundKonsumenPerihal ? "Nominal" : "Harga"
              }}<span class="text-red-500">*</span>
            </label>
            <div v-if="errors.harga" class="text-red-500 text-xs mt-1">
              Form ini wajib di isi
            </div>
          </div>

          <!-- Harga untuk Lainnya -->
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
              Form ini wajib di isi
            </div>
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
          @error="(message) => $emit('addError', message)"
        />
        <div class="text-sm text-gray-600">
          <p v-if="purchaseOrder.dokumen">
            Dokumen saat ini:
            <a
              :href="'/storage/' + purchaseOrder.dokumen"
              target="_blank"
              class="text-blue-600 hover:underline"
              >{{ purchaseOrder.dokumen.split("/").pop() }}</a
            >
          </p>
        </div>
        <div v-if="errors.dokumen" class="text-red-500 text-xs mt-1">
          Form ini wajib di isi
        </div>
      </div>

      <hr class="my-6" />
    </form>
  </div>
</template>

<script setup lang="ts">
import { ref, watch, computed } from "vue";
import CustomSelect from "@/components/ui/CustomSelect.vue";
import FileUpload from "@/components/ui/FileUpload.vue";
import Datepicker from "@vuepic/vue-datepicker";
import "@vuepic/vue-datepicker/dist/main.css";
import { parseCurrency } from "@/lib/currencyUtils";

// Props
const props = defineProps<{
  form: any;
  errors: { [key: string]: string };
  purchaseOrder: any;
  departemenList: any[];
  perihalList: any[];
  supplierList: any[];
  bankList: any[];
  customerOptions: any[];
  creditCardOptions: any[];
  terminList: any[];
  selectedSupplierBankAccounts: any[];
  selectedCreditCardId: string | null;
  selectedCreditCardBankName: string;
  isStaffToko: boolean;
  isRefundKonsumenPerihal: boolean;
  isSpecialPerihal: boolean;
  selectedPerihalName: string;
  datePickerKey: number;
  displayTanggal: string;
  validTanggalGiro: any;
  validTanggalCair: any;
  displayHarga: string;
  dokumenFile: File | null;
  jenisBarangList: any[];
  useBarangDropdown: boolean;
}>();

// Emits
const emit = defineEmits<{
  "update:form": [value: any];
  "update:dokumenFile": [value: File | null];
  "update:selectedCreditCardId": [value: string | null];
  "update:selectedCreditCardBankName": [value: string];
  "update:validTanggalGiro": [value: any];
  "update:validTanggalCair": [value: any];
  showAddPerihalModal: [];
  showAddTerminModal: [];
  addError: [message: string];
  handleCustomerChange: [customerId: string];
  handleCustomerBankChange: [bankId: string];
  handleSupplierChange: [supplierId: string];
  handleBankSupplierAccountChange: [bankSupplierAccountId: string];
  handleSelectCreditCard: [creditCardId: string];
  handleTerminChange: [terminId: string];
  searchCustomers: [query: string];
  searchSuppliers: [query: string];
  searchCreditCards: [query: string];
  searchTermins: [query: string];
  allowNumericKeydown: [event: KeyboardEvent];
  searchJenisBarangs: [query: string];
}>();

// Show Jenis Barang only for Human Greatness or Zi&Glo
const isHGOrZiGlo = computed(() => {
  try {
    const id = (form.value?.department_id ?? '').toString();
    const dep = (props.departemenList || []).find((d: any) => d && d.id !== undefined && d.id !== null && d.id.toString() === id);
    const raw = (dep?.name || dep?.nama || '').toString().toLowerCase();
    // Normalize: remove spaces, underscores, hyphens, and decode common encodings of '&'
    const normalized = raw
      .replace(/\\u0026/g, '&')
      .replace(/&amp;/g, '&')
      .replace(/[^a-z0-9]/g, ''); // keep alphanumerics only
    return normalized === 'humangreatness' || normalized === 'ziglo';
  } catch {
    return false;
  }
});

// Local reactive copies
const form = ref(props.form);
const errors = ref(props.errors);
const dokumenFile = ref(props.dokumenFile);
const selectedCreditCardId = ref(props.selectedCreditCardId);
const selectedCreditCardBankName = ref(props.selectedCreditCardBankName);

// Watch for prop changes
watch(
  () => props.form,
  (newForm) => {
    form.value = newForm;
  },
  { deep: true }
);

watch(
  () => props.errors,
  (newErrors) => {
    errors.value = newErrors;
  },
  { deep: true }
);

watch(
  () => props.dokumenFile,
  (newFile) => {
    dokumenFile.value = newFile;
  }
);

watch(
  () => props.selectedCreditCardId,
  (newId) => {
    selectedCreditCardId.value = newId;
  }
);

watch(
  () => props.selectedCreditCardBankName,
  (newName) => {
    selectedCreditCardBankName.value = newName;
  }
);

// Emit updates
watch(
  form,
  (newForm) => {
    emit("update:form", newForm);
  },
  { deep: true }
);

watch(dokumenFile, (newFile) => {
  emit("update:dokumenFile", newFile);
});

watch(selectedCreditCardId, (newId) => {
  emit("update:selectedCreditCardId", newId);
});

watch(selectedCreditCardBankName, (newName) => {
  emit("update:selectedCreditCardBankName", newName);
});

// Handler functions
function handleCustomerChange(customerId: string) {
  emit("handleCustomerChange", customerId);
}

// Computed: merge currently selected termin into options so it always shows in edit
const terminOptions = computed(() => {
  const base = Array.isArray(props.terminList) ? props.terminList : [];
  const selectedId = (form.value?.termin_id ?? '').toString();
  const exists = base.some((t: any) => t && t.id !== undefined && t.id !== null && t.id.toString() === selectedId);
  let merged = base.slice();
  if (selectedId && !exists) {
    const fromPO = (props.purchaseOrder as any)?.termin;
    const label = fromPO?.no_referensi && String(fromPO.no_referensi).trim() !== ''
      ? fromPO.no_referensi
      : `#${selectedId}`;
    merged = [{ id: selectedId, no_referensi: label, status: fromPO?.status }, ...merged];
  }
  return merged.map((t: any) => ({
    label: t?.no_referensi ?? `#${t?.id}`,
    value: String(t?.id ?? ''),
    disabled: t?.status === 'completed'
  }));
});

function handleCustomerBankChange(bankId: string) {
  emit("handleCustomerBankChange", bankId);
}

function handleSupplierChange(supplierId: string) {
  emit("handleSupplierChange", supplierId);
}

function handleBankSupplierAccountChange(bankSupplierAccountId: string) {
  emit("handleBankSupplierAccountChange", bankSupplierAccountId);
}

function handleSelectCreditCard(creditCardId: string) {
  emit("handleSelectCreditCard", creditCardId);
}

function handleTerminChange(terminId: string) {
  emit("handleTerminChange", terminId);
}

function searchCustomers(query: string) {
  emit("searchCustomers", query);
}

function searchSuppliers(query: string) {
  emit("searchSuppliers", query);
}

function searchCreditCards(query: string) {
  emit("searchCreditCards", query);
}

function searchTermins(query: string) {
  emit("searchTermins", query);
}

function allowNumericKeydown(event: KeyboardEvent) {
  emit("allowNumericKeydown", event);
}

function searchJenisBarangs(query: string) {
  emit("searchJenisBarangs", query);
}

// Local handlers inside component
function onSubmit() {
  // Intentionally left as no-op to prevent runtime error when form submits
  // Parent may handle submission via external buttons/actions
}

function onHargaInput(event: Event) {
  const inputElement = event.target as HTMLInputElement;
  const rawValue = inputElement?.value ?? "";
  try {
    const parsedValue = parseCurrency(rawValue);
    if (form.value) {
      form.value.harga = parsedValue;
    }
  } catch {
    // Fallback: do not update harga if parsing fails
  }
}
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

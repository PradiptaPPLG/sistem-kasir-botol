<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Kasir · Transaksi</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 p-4">

<div class="max-w-3xl mx-auto">

<!-- HEADER -->
<div class="bg-white rounded-2xl shadow p-6 flex items-center gap-4 mb-6">
    <span class="text-4xl">💳</span>
    <h1 class="text-2xl font-bold">Transaksi Kasir</h1>
</div>

<!-- TAB -->
<div class="bg-white rounded-full shadow flex p-2 mb-6">
    <button onclick="location='?type=pembeli'" 
        class="flex-1 py-3 rounded-full font-bold <?= $type=='pembeli'?'bg-blue-600 text-white':'text-gray-600' ?>">
        👤 Pembeli
    </button>
    <button onclick="location='?type=penjual'" 
        class="flex-1 py-3 rounded-full font-bold <?= $type=='penjual'?'bg-blue-600 text-white':'text-gray-600' ?>">
        🏪 Penjual
    </button>
</div>

<div class="bg-white shadow rounded-2xl p-6">

<?php if($success): ?>
<div class="bg-green-100 text-green-700 p-3 rounded-xl mb-4 font-semibold">✅ <?= $success ?></div>
<?php endif; ?>

<?php if($error): ?>
<div class="bg-red-100 text-red-700 p-3 rounded-xl mb-4 font-semibold">❌ <?= $error ?></div>
<?php endif; ?>

<form method="POST">

<input type="hidden" name="jenis_pembeli" value="<?= $type ?>">

<!-- BARANG -->
<div class="mb-4">
<label class="font-semibold text-lg">Pilih Barang</label>
<select name="id_barang" id="barangSelect" onchange="updateInfo()" required
class="w-full p-4 border rounded-xl text-lg mt-2">
<option value="">-- Pilih Barang --</option>

<?php while ($b = $barang_result->fetch_assoc()): ?>
<option value="<?= $b['id_barang'] ?>"
data-harga-beli="<?= $b['harga_beli'] ?>"
data-harga-jual="<?= $b['harga_jual'] ?>"
data-stok="<?= $b['stok_tersedia'] ?>">
<?= $b['nama_barang'] ?> (stok <?= $b['stok_tersedia'] ?>)
</option>
<?php endwhile; ?>

</select>

<div id="stokInfo" class="mt-2 bg-gray-100 p-3 rounded-xl hidden font-semibold">
📦 Stok tersedia: <span id="stokValue"></span>
</div>
</div>

<!-- JUMLAH -->
<div class="mb-4">
<label class="font-semibold text-lg">Jumlah</label>
<input type="number" name="jumlah" id="jumlahInput" value="1" min="1"
oninput="hitungTotal()" 
class="w-full p-4 border rounded-xl text-lg mt-2">
</div>

<?php if($type=='penjual'): ?>
<div class="mb-4">
<label class="font-semibold text-lg">Harga Jual</label>
<input type="number" name="harga_jual" id="hargaJualInput"
oninput="hitungTotal()"
class="w-full p-4 border rounded-xl text-lg mt-2">
</div>

<div id="keuntunganBox" class="bg-yellow-100 p-4 rounded-xl hidden">
Harga beli: <span id="hargaBeliText"></span><br>
Keuntungan per botol: <b id="keuntunganPerText"></b>
</div>
<?php endif; ?>

<!-- TOTAL -->
<div id="totalBox" class="bg-green-100 p-5 rounded-xl text-center hidden mt-4">
<div class="text-lg font-semibold">TOTAL</div>
<div id="totalText" class="text-3xl font-bold text-green-700"></div>
</div>

<button type="submit"
class="w-full bg-blue-600 text-white text-xl font-bold py-4 rounded-xl mt-6">
💾 Proses Transaksi
</button>

</form>

<a href="index.php" class="block text-center mt-4 text-blue-600 font-semibold">← Kembali</a>

</div>
</div>

<script>
let hargaBeli=0, hargaJual=0, stokTersedia=0;

function updateInfo() {
    const select = document.getElementById("barangSelect");
    const opt = select.options[select.selectedIndex];
    if(!opt.value) return;

    hargaBeli = parseFloat(opt.dataset.hargaBeli);
    hargaJual = parseFloat(opt.dataset.hargaJual);
    stokTersedia = parseInt(opt.dataset.stok);

    document.getElementById("stokInfo").classList.remove("hidden");
    document.getElementById("stokValue").innerText = stokTersedia;

    document.getElementById("jumlahInput").max = stokTersedia;

    <?php if($type=='penjual'): ?>
    document.getElementById("hargaJualInput").value = hargaJual;
    document.getElementById("keuntunganBox").classList.remove("hidden");
    document.getElementById("hargaBeliText").innerText = formatRupiah(hargaBeli);
    <?php endif; ?>

    hitungTotal();
}

function hitungTotal() {
    const j = parseInt(document.getElementById("jumlahInput").value)||0;
    if(j>stokTersedia){ alert("Melebihi stok"); return; }

    let total = 0;

    <?php if($type=='pembeli'): ?>
    total = j * hargaBeli;
    <?php else: ?>
    const hj = parseFloat(document.getElementById("hargaJualInput").value)||0;
    total = j * hj;
    document.getElementById("keuntunganPerText").innerText = formatRupiah(hj-hargaBeli);
    <?php endif; ?>

    document.getElementById("totalBox").classList.remove("hidden");
    document.getElementById("totalText").innerText = formatRupiah(total);
}

function formatRupiah(n){
    return "Rp " + n.toLocaleString("id-ID");
}
</script>

</body>
</html>

/**
 * Builder key-value sederhana untuk kolom JSON spesifikasi_fasilitas.
 * Setiap baris (key,value) digabung jadi satu objek JSON sebelum submit,
 * lalu ditulis ke input hidden #fasilitas_json.
 */
function tambahBarisFasilitas(key = '', value = '') {
  const wrap = document.getElementById('fasilitas-rows');
  const row = document.createElement('div');
  row.className = 'json-builder-row';
  row.innerHTML = `
    <input type="text" placeholder="Nama fasilitas (mis. RAM, Proyektor)" class="fk" value="${key.replace(/"/g, '&quot;')}">
    <input type="text" placeholder="Nilai (mis. 16GB, atau: true / Ya)" class="fv" value="${value.replace(/"/g, '&quot;')}">
    <button type="button" class="btn btn-danger btn-sm" onclick="this.parentElement.remove()">Hapus</button>
  `;
  wrap.appendChild(row);
}

function siapkanFasilitasJSON(formEl) {
  const rows = formEl.querySelectorAll('#fasilitas-rows .json-builder-row');
  const hasil = {};
  rows.forEach(row => {
    const k = row.querySelector('.fk').value.trim();
    let v = row.querySelector('.fv').value.trim();
    if (!k) return;
    if (v.toLowerCase() === 'true' || v.toLowerCase() === 'ya') v = true;
    else if (v.toLowerCase() === 'false' || v.toLowerCase() === 'tidak') v = false;
    else if (v.includes(',')) v = v.split(',').map(s => s.trim()).filter(Boolean);
    hasil[k] = v;
  });
  document.getElementById('fasilitas_json').value = JSON.stringify(hasil);
  return true;
}

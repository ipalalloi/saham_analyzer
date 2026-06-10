document.addEventListener('click', function(e){
  if(e.target.matches('[data-toggle-sidebar]')) document.querySelector('.sidebar')?.classList.toggle('open');
  if(e.target.matches('[data-modal-open]')) document.querySelector(e.target.dataset.modalOpen)?.classList.add('show');
  if(e.target.matches('[data-modal-close]')) e.target.closest('.modal')?.classList.remove('show');
});
function confirmDelete(message){ return confirm(message || 'Hapus data ini? Aksi akan terekam di audit log.'); }
function fillCompanyForm(row){
  const data = JSON.parse(row.dataset.row || '{}');
  for (const [key,val] of Object.entries(data)) {
    const el = document.querySelector(`[name="${key}"]`);
    if (!el) continue;
    if (el.type === 'checkbox') el.checked = String(val) === '1'; else el.value = val ?? '';
  }
  document.querySelector('#companyModal')?.classList.add('show');
}
function fillCycleForm(row){
  const data = JSON.parse(row.dataset.row || '{}');
  for (const [key,val] of Object.entries(data)) { const el=document.querySelector(`[name="${key}"]`); if(el) el.value=val ?? ''; }
  document.querySelector('#cycleModal')?.classList.add('show');
}
function fillSourceForm(row){
  const data = JSON.parse(row.dataset.row || '{}');
  for (const [key,val] of Object.entries(data)) { const el=document.querySelector(`[name="${key}"]`); if(el) el.value=val ?? ''; }
  document.querySelector('#sourceModal')?.classList.add('show');
}

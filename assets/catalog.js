(() => {
const $=s=>document.querySelector(s), page=document.body.dataset.page;
let rows=[];
async function api(action,opts={}){const r=await fetch('api.php?action='+action,opts);const j=await r.json();if(!j.ok)throw new Error(j.error||'Request failed');return j.data}
function esc(s){return String(s??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]))}
function sortRows(a){
 const sort=$('#pageSort')?.value||'updated';
 return [...a].sort((x,y)=>sort==='name'?x.name.localeCompare(y.name):sort==='oldest'?x.created_at.localeCompare(y.created_at):sort==='newest'?y.created_at.localeCompare(x.created_at):(y.updated_at||'').localeCompare(x.updated_at||''));
}
function render(){
 const q=$('#pageSearch').value.trim().toLowerCase();
 let view=rows.filter(x=>x.name.toLowerCase().includes(q));
 if(page==='library')view=sortRows(view);
 $('#pageGrid').innerHTML=view.map(x=>`<article class="catalog-card"><img src="${esc(x.thumbnail||x.file)}" alt=""><div class="catalog-body"><strong>${esc(x.name)}</strong><span>${x.width}×${x.height}</span><div class="row"><a class="button-link primary" href="index.php?trophy=${encodeURIComponent(x.id)}${page==='settings'?'&configured=1':''}">Open in engraver</a>${page==='library'?'<button class="rename" data-id="'+x.id+'">Rename</button><button class="delete danger" data-id="'+x.id+'">Delete</button>':''}</div></div></article>`).join('') || '<div class="empty-card">Nothing here yet.</div>';
 if(page==='library'){
  document.querySelectorAll('.rename').forEach(b=>b.onclick=async()=>{const item=rows.find(x=>x.id===b.dataset.id),name=prompt('Artwork name',item?.name||'');if(!name)return;await api('rename',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id:b.dataset.id,name})});await load()});
  document.querySelectorAll('.delete').forEach(b=>b.onclick=async()=>{if(!confirm('Delete this artwork and its saved settings?'))return;await api('delete',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id:b.dataset.id})});await load()});
 }
}
async function load(){rows=await api(page==='settings'?'profiles':'list');render()}
$('#pageSearch').addEventListener('input',render);$('#pageSort')?.addEventListener('change',render);
if(page==='library'){
 $('#pageUploadBtn').onclick=()=>$('#pageUploadInput').click();
 $('#pageUploadInput').onchange=async()=>{const f=$('#pageUploadInput').files[0];if(!f)return;const fd=new FormData();fd.append('image',f);fd.append('name',f.name.replace(/\.[^.]+$/,''));const x=await api('upload',{method:'POST',body:fd});location.href='index.php?trophy='+encodeURIComponent(x.id)};
}
load().catch(e=>alert(e.message));
})();
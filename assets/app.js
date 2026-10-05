(() => {
const $=s=>document.querySelector(s);
const canvas=$('#canvas'),ctx=canvas.getContext('2d');
const state={library:[],selected:null,profile:null,img:null,zoneVisible:true,drawing:false,start:null};
const material={gold:['#7c5720','#f7dc83','#a77527'],silver:['#67717a','#f3f5f6','#848e97'],bronze:['#6f3b20','#d78b4f','#7a4224'],dark:['#080808','#555','#090909'],light:['#aaa','#fff','#bbb']};

async function api(action,opts={}){const r=await fetch('api.php?action='+action,opts);const j=await r.json();if(!j.ok)throw new Error(j.error||'Request failed');return j.data}
function esc(s){return String(s??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]))}
function debounce(fn,ms=180){let t;return(...a)=>{clearTimeout(t);t=setTimeout(()=>fn(...a),ms)}}

async function refresh(){
 state.library=await api('list');
 renderLibrary();
 if(state.selected){const newer=state.library.find(x=>x.id===state.selected.id);if(newer)state.selected=newer}
}
function renderLibrary(){
 let rows=[...state.library],q=$('#search').value.trim().toLowerCase(),sort=$('#sort').value;
 if(q)rows=rows.filter(x=>x.name.toLowerCase().includes(q));
 rows.sort((a,b)=>sort==='name'?a.name.localeCompare(b.name):sort==='oldest'?a.created_at.localeCompare(b.created_at):sort==='newest'?b.created_at.localeCompare(a.created_at):b.updated_at.localeCompare(a.updated_at));
 $('#libraryGrid').innerHTML=rows.map(x=>`<div class="card ${state.selected?.id===x.id?'active':''}" data-id="${x.id}"><img src="${esc(x.thumbnail||x.file)}" alt=""><strong>${esc(x.name)}</strong><span>${x.width}×${x.height}</span></div>`).join('');
 document.querySelectorAll('.card').forEach(c=>c.onclick=()=>loadTrophy(c.dataset.id));
}

function makeLines(){
 $('#lines').innerHTML=[0,1,2].map((i)=>`<div class="line-row"><input class="lineText" data-i="${i}" type="text" placeholder="Line ${i+1}"><input class="lineSize" data-i="${i}" type="number" min="6" max="300" value="${[42,32,26][i]}"></div>`).join('');
 document.querySelectorAll('.lineText,.lineSize').forEach(el=>el.addEventListener('input',()=>{syncFromUI();render()}));
}
makeLines();

async function loadTrophy(id){
 const item=state.library.find(x=>x.id===id);if(!item)return;
 const profile=await api('profile&id='+encodeURIComponent(id));
 const img=new Image(); img.onload=()=>{state.selected=item;state.profile=profile;state.img=img;canvas.width=img.naturalWidth;canvas.height=img.naturalHeight;canvas.style.display='block';$('#emptyState').style.display='none';syncToUI();renderMeta();renderLibrary();render()};img.src=item.file+'?v='+encodeURIComponent(item.updated_at||'');
}
function settings(){return state.profile?.defaults||{}}
function syncToUI(){
 const s=settings();(s.lines||[]).forEach((l,i)=>{document.querySelector(`.lineText[data-i="${i}"]`).value=l.text||'';document.querySelector(`.lineSize[data-i="${i}"]`).value=l.size||30});
 $('#lineSpacing').value=s.lineSpacing??1;$('#offsetX').value=s.offsetX??0;$('#offsetY').value=s.offsetY??0;$('#font').value=s.font||'Georgia';$('#material').value=s.material||'gold';$('#projection').value=s.projection||'flat';$('#curve').value=s.curve??0;
}
function syncFromUI(){
 if(!state.profile)return;const s=settings();
 s.lines=[0,1,2].map(i=>({text:document.querySelector(`.lineText[data-i="${i}"]`).value,size:+document.querySelector(`.lineSize[data-i="${i}"]`).value||30}));
 s.lineSpacing=+$('#lineSpacing').value;s.offsetX=+$('#offsetX').value||0;s.offsetY=+$('#offsetY').value||0;s.font=$('#font').value;s.material=$('#material').value;s.projection=$('#projection').value;s.curve=+$('#curve').value||0;
}
function renderMeta(){
 const x=state.selected;$('#selectedMeta').innerHTML=x?`<div class="meta-name">${esc(x.name)}</div><div class="meta-sub">${x.width} × ${x.height}px<br>${esc(x.mime)}</div>`:'None';
}
function drawText(text,y,size,z,s){
 if(!text)return;ctx.save();ctx.textAlign='center';ctx.textBaseline='middle';ctx.font=`600 ${size}px "${s.font}"`;
 const colors=material[s.material]||material.gold;const grad=ctx.createLinearGradient(z.x,y-size/2,z.x+z.w,y+size/2);grad.addColorStop(0,colors[0]);grad.addColorStop(.45,colors[1]);grad.addColorStop(1,colors[2]);ctx.fillStyle=grad;ctx.shadowColor='rgba(0,0,0,.45)';ctx.shadowBlur=Math.max(1,size*.06);ctx.shadowOffsetY=Math.max(1,size*.04);
 if(s.projection==='cylindrical' && s.curve!==0){const chars=[...text],total=ctx.measureText(text).width,start=z.x+z.w/2-total/2;let x=start;chars.forEach(ch=>{const w=ctx.measureText(ch).width,mid=x+w/2-z.x-z.w/2,ny=y+(s.curve/100)*(mid*mid)/(z.w*.9);ctx.fillText(ch,x+w/2,ny);x+=w})} else ctx.fillText(text,z.x+z.w/2,y);
 ctx.restore();
}
function render(){
 if(!state.img)return;ctx.clearRect(0,0,canvas.width,canvas.height);ctx.drawImage(state.img,0,0);
 const z=state.profile?.zone;if(!z)return;
 const px={x:z.x*canvas.width,y:z.y*canvas.height,w:z.w*canvas.width,h:z.h*canvas.height};
 const s=settings(),lines=s.lines||[];const active=lines.map(l=>l.text?l:null).filter(Boolean);let heights=active.map(l=>+l.size||30),spacing=(+s.lineSpacing||1)*Math.max(...heights,1)*.35,total=heights.reduce((a,b)=>a+b,0)+spacing*Math.max(0,active.length-1),cy=px.y+px.h/2-total/2+(+s.offsetY||0);
 for(const l of active){const h=+l.size||30;drawText(l.text,cy+h/2,h,{...px,x:px.x+(+s.offsetX||0)},s);cy+=h+spacing}
 if(state.zoneVisible){ctx.save();ctx.fillStyle='rgba(62,156,255,.15)';ctx.strokeStyle='rgba(96,187,255,.9)';ctx.lineWidth=Math.max(2,canvas.width/700);ctx.setLineDash([10,7]);ctx.fillRect(px.x,px.y,px.w,px.h);ctx.strokeRect(px.x,px.y,px.w,px.h);ctx.restore()}
}
function point(e){const r=canvas.getBoundingClientRect();return{x:(e.clientX-r.left)*canvas.width/r.width,y:(e.clientY-r.top)*canvas.height/r.height}}
canvas.addEventListener('pointerdown',e=>{if(!state.img||!state.drawing)return;canvas.setPointerCapture(e.pointerId);state.start=point(e);state.profile.zone={x:state.start.x/canvas.width,y:state.start.y/canvas.height,w:0,h:0};render()});
canvas.addEventListener('pointermove',e=>{if(!state.start||!state.drawing)return;const p=point(e),x=Math.min(state.start.x,p.x),y=Math.min(state.start.y,p.y),w=Math.abs(p.x-state.start.x),h=Math.abs(p.y-state.start.y);state.profile.zone={x:x/canvas.width,y:y/canvas.height,w:w/canvas.width,h:h/canvas.height};render()});
canvas.addEventListener('pointerup',()=>{if(!state.start)return;state.start=null;state.drawing=false;$('#zoneMode').classList.remove('primary');render()});

$('#zoneMode').onclick=()=>{if(!state.img)return;state.drawing=!state.drawing;$('#zoneMode').classList.toggle('primary',state.drawing)};
$('#toggleZone').onclick=()=>{state.zoneVisible=!state.zoneVisible;$('#toggleZone').textContent=state.zoneVisible?'Hide zone':'Show zone';render()};
['lineSpacing','offsetX','offsetY','font','material','projection','curve'].forEach(id=>$('#'+id).addEventListener('input',()=>{syncFromUI();render()}));
document.querySelectorAll('[data-nudge]').forEach(b=>b.onclick=()=>{if(!state.profile)return;const [x,y]=b.dataset.nudge.split(',').map(Number);$('#offsetX').value=(+$('#offsetX').value||0)+x;$('#offsetY').value=(+$('#offsetY').value||0)+y;syncFromUI();render()});
$('#uploadBtn').onclick=()=>$('#uploadInput').click();
$('#uploadInput').onchange=async()=>{const f=$('#uploadInput').files[0];if(!f)return;const fd=new FormData();fd.append('image',f);fd.append('name',f.name.replace(/\.[^.]+$/,''));try{const x=await api('upload',{method:'POST',body:fd});await refresh();await loadTrophy(x.id)}catch(e){alert(e.message)}finally{$('#uploadInput').value=''}};
$('#saveDefaults').onclick=async()=>{if(!state.selected)return;syncFromUI();try{state.profile=await api('profile&id='+encodeURIComponent(state.selected.id),{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(state.profile)});alert('Trophy profile saved.')}catch(e){alert(e.message)}};
$('#renameBtn').onclick=async()=>{if(!state.selected)return;const name=prompt('Trophy name',state.selected.name);if(!name)return;try{state.selected=await api('rename',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id:state.selected.id,name})});await refresh();renderMeta()}catch(e){alert(e.message)}};
$('#deleteBtn').onclick=async()=>{if(!state.selected||!confirm('Delete this trophy and its profile?'))return;try{await api('delete',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id:state.selected.id})});state.selected=state.profile=state.img=null;canvas.style.display='none';$('#emptyState').style.display='block';renderMeta();await refresh()}catch(e){alert(e.message)}};
$('#exportPng').onclick=()=>{if(!state.img)return;const old=state.zoneVisible;state.zoneVisible=false;render();canvas.toBlob(blob=>{const a=document.createElement('a');a.href=URL.createObjectURL(blob);a.download=(state.selected?.name||'engraved-trophy').replace(/[^a-z0-9._-]+/gi,'-')+'.png';a.click();setTimeout(()=>URL.revokeObjectURL(a.href),1000)},'image/png');state.zoneVisible=old;render()};
$('#search').addEventListener('input',debounce(renderLibrary));$('#sort').onchange=renderLibrary;
refresh().catch(e=>alert(e.message));
})();
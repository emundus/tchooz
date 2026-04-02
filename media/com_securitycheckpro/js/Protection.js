"use strict";

const size = Joomla.getOptions("securitycheckpro.Protection.blockedaccessText", 20);
const $ = (s, c=document)=>c.querySelector(s);
const $$ = (s, c=document)=>Array.from(c.querySelectorAll(s));
const on = (el, ev, fn, o)=>el&&el.addEventListener(ev, fn, o);

const Password = {
  _pattern:/[a-zA-Z0-9]/,
  _rand(){
    if (crypto?.getRandomValues) { const r=new Uint8Array(1); crypto.getRandomValues(r); return r[0]; }
    if (window.msCrypto?.getRandomValues){ const r=new Uint8Array(1); window.msCrypto.getRandomValues(r); return r[0]; }
    return Math.floor(Math.random()*256);
  },
  generate(n){ const out=[]; while(out.length<n){const ch=String.fromCharCode(this._rand()); if(this._pattern.test(ch)) out.push(ch);} return out.join(''); }
};

function wireTabsetFallback(navSel, key){
  const nav=$(navSel); if(!nav) return;
  const links=$$('a[data-bs-toggle="tab"]', nav);
  const saved=localStorage.getItem(key);
  if(saved){ const a=links.find(x=>x.getAttribute('href')===`#${saved}`); if(a) new bootstrap.Tab(a).show(); }
  links.forEach(a=>on(a,'shown.bs.tab',e=>{
    const id=(e.target.getAttribute('href')||'').replace(/^#/,''); if(id) localStorage.setItem(key,id);
  }));
}

function wireWwwSelectors(){
  const nonWww=$('#redirect_to_non_www'), www=$('#redirect_to_www');
  if(!nonWww || !www) return;
  on(nonWww,'change',()=>{ if(String(nonWww.value)==='1') www.value='0'; });
  on(www,'change',()=>{ if(String(www.value)==='1') nonWww.value='0'; });
}

function hideIt(){
  const chk=$('#backend_protection_applied');
  const blocks=['#menu_hide_backend_1','#menu_hide_backend_2','#menu_hide_backend_3','#menu_hide_backend_4','#block','#block2','#block3','#block4']
    .map(sel=>$(sel)).filter(Boolean);
  if(!chk) return;
  if(chk.checked){
    blocks.forEach(el=>el.classList.add('d-none'));
    const url=$('#hide_backend_url'); const exc=$('#backend_exceptions');
    if(url) url.value=''; if(exc) exc.value='';
    chk.value='1';
  }else{
    blocks.forEach(el=>el.classList.remove('d-none'));
    chk.value='0';
  }
}

function showDefaultUserAgentsModal(){
  const el=$('#div_default_user_agents'); if(!el) return;
  bootstrap.Modal.getOrCreateInstance(el).show();
}

/* Expand/Close para TODAS las textareas con .scp-expander */
function wireExpanders(){
  $$('#adminForm .scp-expander').forEach(btn=>{
    on(btn,'click',()=>{
      const sel=btn.getAttribute('data-expand-target');
      const ta=sel?$(sel):null; if(!ta) return;
      const expanded = ta.classList.toggle('is-expanded');
      btn.classList.toggle('is-expanded', expanded);
      btn.textContent = expanded ? 'Close' : 'Expand';
      btn.classList.toggle('btn-danger', expanded);
      btn.classList.toggle('btn-outline-secondary', !expanded);
      document.body.style.overflow = expanded ? 'hidden' : '';
    });
  });

  // ESC para cerrar
  on(document,'keydown',(e)=>{
    if(e.key!=='Escape') return;
    const ta=$('#adminForm .scp-textarea.is-expanded');
    const btn=$('#adminForm .scp-expander.is-expanded');
    if(ta){ ta.classList.remove('is-expanded'); }
    if(btn){ btn.classList.remove('is-expanded'); btn.textContent='Expand'; btn.classList.remove('btn-danger'); btn.classList.add('btn-outline-secondary'); }
    if(ta||btn){ document.body.style.overflow=''; }
  });
}

document.addEventListener('DOMContentLoaded', () => {
  const form = $('#adminForm');
  if (form) {
    form.classList.add('scp-compact');
  }
  
  var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
  tooltipTriggerList.forEach(function (el) { new bootstrap.Tooltip(el); });
  
  const group = document.getElementById('backend_actions_group');
  const expander = document.querySelector('#backend_exceptions + .scp-expander');
  if (!group || !expander) return;

  group.addEventListener('show.bs.dropdown', () => {
    expander.classList.add('invisible');  // o 'd-none' si prefieres
  });
  group.addEventListener('hidden.bs.dropdown', () => {
    expander.classList.remove('invisible');
  });

  if (typeof window.wireTabset === 'function') window.wireTabset('#protectionTab','active_htaccess');
  else wireTabsetFallback('#protectionTab','active_htaccess');

  wireWwwSelectors();
  wireExpanders();

  on($('#save_default_user_agent_button'),'click',()=>Joomla.submitbutton('save_default_user_agent'));
  on($('#boton_default_user_agent'),'click',showDefaultUserAgentsModal);
  on($('#hide_backend_url_button'),'click',()=>{ const t=$('#hide_backend_url'); if(t) t.value=Password.generate(size); });
  on($('#add_exception_button'),'click',()=>{ const i=$('#exception'), a=$('#backend_exceptions'); if(!i||!a) return; const v=(i.value||'').trim(); if(!v) return; a.value=a.value.trim().length?`${a.value},${v}`:v; i.value=''; });
  on($('#delete_exception_button'),'click',()=>{ const i=$('#exception'), a=$('#backend_exceptions'); if(!i||!a) return; const v=(i.value||'').trim(); if(!v) return; const rx1=new RegExp(`,\\s*${v.replace(/[.*+?^${}()|[\\]\\\\]/g,'\\$&')}`,'g'); const rx2=new RegExp(`${v.replace(/[.*+?^${}()|[\\]\\\\]/g,'\\$&')}\\s*,`,'g'); const rx3=new RegExp(`${v.replace(/[.*+?^${}()|[\\]\\\\]/g,'\\$&')}`,'g'); let t=a.value; [rx1,rx2,rx3].forEach(rx=>t=t.replace(rx,'')); t=t.replace(/,{2,}/g,',').replace(/^\s*,|,\s*$/g,'').trim(); a.value=t; i.value=''; });
  on($('#delete_all_button'),'click',()=>{ const a=$('#backend_exceptions'); if(a) a.value=''; });
  on($('#backend_protection_applied'),'change',hideIt);
  hideIt();
});

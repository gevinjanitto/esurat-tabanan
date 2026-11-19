// Halaman HTML mandiri untuk merender PDF (pdf.js) + lapisan coretan.
// Format data sama persis dengan annotator web: { strokes, notes, parafs } dengan koordinat relatif 0..1.
export const STUDIO_HTML = `<!doctype html><html><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=4">
<style>
html,body{margin:0;padding:0;background:#F2EFEA;font-family:-apple-system,Roboto,Segoe UI,sans-serif;-webkit-user-select:none;user-select:none;-webkit-tap-highlight-color:transparent}
#pages{padding:12px 12px 160px;display:flex;flex-direction:column;gap:12px}
body.compact #pages{padding-bottom:12px}
.page{position:relative;background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 14px rgba(26,25,24,.08)}
.page>canvas.pdf{display:block;width:100%}
.layer{position:absolute;left:0;top:0;right:0;bottom:0;touch-action:pan-x pan-y pinch-zoom}
.layer.drawing{touch-action:none}
.layer canvas{position:absolute;left:0;top:0;width:100%;height:100%}
.pg{position:absolute;right:8px;bottom:8px;background:rgba(26,25,24,.62);color:#fff;font-size:11px;font-weight:700;padding:3px 9px;border-radius:99px;pointer-events:none}
.note{position:absolute;width:28px;height:28px;margin-top:-28px;border-radius:14px 14px 14px 3px;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800;font-size:14px;box-shadow:0 3px 8px rgba(0,0,0,.25);border:2px solid #fff;box-sizing:border-box}
.note.own{background:#E63946}.note.other{background:#457B9D}
.paraf{position:absolute;border:1.5px dashed #E63946;border-radius:8px;display:flex;align-items:center;justify-content:center;background:rgba(253,236,238,.35);box-sizing:border-box;touch-action:none}
.paraf.locked{border-color:#457B9D;background:rgba(69,123,157,.08)}
.paraf img{max-width:100%;max-height:100%;object-fit:contain;pointer-events:none}
.paraf .ini{font-style:italic;font-weight:800;color:#7A1020;font-size:13px;pointer-events:none}
.paraf .rs{position:absolute;right:-9px;bottom:-9px;width:18px;height:18px;border-radius:9px;background:#E63946;border:2px solid #fff}
.paraf .del{position:absolute;right:-10px;top:-10px;width:22px;height:22px;border-radius:11px;background:#1A1918;color:#fff;font-size:12px;line-height:18px;text-align:center;border:2px solid #fff;box-sizing:border-box}
.msg{padding:56px 24px;text-align:center;color:#7D7975;font-size:14px}
.sk{height:440px;border-radius:16px;background:linear-gradient(90deg,#ece7e1,#f6f2ee,#ece7e1);background-size:200% 100%;animation:sh 1.2s infinite linear}
@keyframes sh{to{background-position:-200% 0}}
</style>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js"></script>
</head><body>
<div id="pages"><div class="sk"></div><div class="sk"></div></div>
<script>
(function(){
  var root=document.getElementById('pages');
  var cfg=null, mine={strokes:[],notes:[],parafs:[]}, others=[], pages=[];
  var tool='hand', color='#e11d48', size=3, undo=[], lastW=0, buildId=0;

  function post(m){var s=JSON.stringify(m); if(window.ReactNativeWebView){window.ReactNativeWebView.postMessage(s);} else {parent.postMessage({__pdfstudio:true,payload:s},'*');}}
  function empty(){return {strokes:[],notes:[],parafs:[]};}
  function norm(d){d=d||{};return {strokes:d.strokes||[],notes:d.notes||[],parafs:d.parafs||[]};}
  function snapshot(){undo.push(JSON.stringify(mine)); if(undo.length>60) undo.shift();}
  function changed(){post({type:'changed',data:mine,canUndo:undo.length>0});}
  function clamp(v,a,b){return Math.min(b,Math.max(a,v));}
  function initials(n){return (n||'').split(/\\s+/).slice(0,2).map(function(s){return s[0]||'';}).join('').toUpperCase();}

  function setTool(t){tool=t; pages.forEach(function(p){p.layer.classList.toggle('drawing', t!=='hand' && !!(cfg&&cfg.editable));});}

  function drawStroke(ctx,s,w,h,alpha){
    if(!s.points||!s.points.length) return;
    ctx.save();
    ctx.globalAlpha=(s.tool==='highlighter'?.32:.95)*alpha;
    ctx.strokeStyle=s.color; ctx.lineWidth=s.size*w;
    ctx.lineCap=s.tool==='highlighter'?'butt':'round'; ctx.lineJoin='round';
    ctx.beginPath(); ctx.moveTo(s.points[0][0]*w,s.points[0][1]*h);
    for(var i=1;i<s.points.length;i++){var a=s.points[i-1],b=s.points[i];ctx.quadraticCurveTo(a[0]*w,a[1]*h,((a[0]+b[0])/2)*w,((a[1]+b[1])/2)*h);}
    if(s.points.length===1) ctx.lineTo(s.points[0][0]*w+.1,s.points[0][1]*h);
    ctx.stroke(); ctx.restore();
  }

  function redraw(p){
    p.ctx.clearRect(0,0,p.width,p.height);
    others.forEach(function(o){o.data.strokes.filter(function(s){return s.page===p.n;}).forEach(function(s){drawStroke(p.ctx,s,p.width,p.height,.75);});});
    mine.strokes.filter(function(s){return s.page===p.n;}).forEach(function(s){drawStroke(p.ctx,s,p.width,p.height,1);});
    Array.prototype.slice.call(p.layer.querySelectorAll('.note,.paraf')).forEach(function(el){el.remove();});
    others.forEach(function(o){
      o.data.notes.forEach(function(n){if(n.page===p.n) addNote(p,n,-1,o.author);});
      o.data.parafs.forEach(function(pf){if(pf.page===p.n) addParaf(p,pf,false,o.author);});
    });
    mine.notes.forEach(function(n,i){if(n.page===p.n) addNote(p,n,i,cfg.userName);});
    mine.parafs.forEach(function(pf){if(pf.page===p.n) addParaf(p,pf,!!cfg.editable,cfg.userName);});
  }
  function redrawAll(){pages.forEach(redraw);}

  function rel(p,e){var r=p.layer.getBoundingClientRect();return [clamp((e.clientX-r.left)/r.width,0,1),clamp((e.clientY-r.top)/r.height,0,1)];}

  function addNote(p,n,idx,author){
    var el=document.createElement('div');
    el.className='note '+(idx>=0?'own':'other');
    el.style.left=(n.x*100)+'%'; el.style.top=(n.y*100)+'%';
    el.textContent='\\u270E';
    el.addEventListener('pointerdown',function(e){e.stopPropagation();});
    el.addEventListener('click',function(e){e.stopPropagation(); post({type:'noteTap',own:idx>=0 && !!cfg.editable,index:idx,author:author,text:n.text,page:n.page});});
    p.layer.appendChild(el);
  }

  function addParaf(p,pf,own,author){
    var el=document.createElement('div');
    el.className='paraf'+(own?'':' locked');
    el.style.left=(pf.x*100)+'%'; el.style.top=(pf.y*100)+'%'; el.style.width=(pf.w*100)+'%'; el.style.height=(pf.h*100)+'%';
    var ini=document.createElement('span'); ini.className='ini'; ini.textContent=initials(author);
    if(own && cfg.parafUrl){var img=document.createElement('img'); img.src=cfg.parafUrl; img.onerror=function(){img.remove(); el.appendChild(ini);}; el.appendChild(img);} else el.appendChild(ini);
    if(own){
      var rs=document.createElement('span'); rs.className='rs'; el.appendChild(rs);
      var del=document.createElement('div'); del.className='del'; del.textContent='\\u2715'; el.appendChild(del);
      del.addEventListener('pointerdown',function(e){e.stopPropagation();});
      del.addEventListener('click',function(e){e.stopPropagation(); snapshot(); mine.parafs.splice(mine.parafs.indexOf(pf),1); redraw(p); changed();});
      var mode=null,start=null;
      el.addEventListener('pointerdown',function(e){
        if(e.target===del) return;
        e.stopPropagation(); e.preventDefault();
        mode=e.target===rs?'resize':'move'; start={pt:rel(p,e),x:pf.x,y:pf.y,w:pf.w,h:pf.h};
        try{el.setPointerCapture(e.pointerId);}catch(_){}
        snapshot();
      });
      el.addEventListener('pointermove',function(e){
        if(!mode) return; e.preventDefault();
        var q=rel(p,e),dx=q[0]-start.pt[0],dy=q[1]-start.pt[1];
        if(mode==='move'){pf.x=clamp(start.x+dx,0,1-pf.w); pf.y=clamp(start.y+dy,0,1-pf.h);} else {pf.w=clamp(start.w+dx,.04,.5); pf.h=clamp(start.h+dy,.02,.3);}
        el.style.left=(pf.x*100)+'%'; el.style.top=(pf.y*100)+'%'; el.style.width=(pf.w*100)+'%'; el.style.height=(pf.h*100)+'%';
      });
      var end=function(){if(mode){mode=null; changed();}};
      el.addEventListener('pointerup',end); el.addEventListener('pointercancel',end);
    }
    p.layer.appendChild(el);
  }

  function erase(p,x,y){
    var r=14/p.width, before=mine.strokes.length;
    mine.strokes=mine.strokes.filter(function(s){return s.page!==p.n || !s.points.some(function(pt){return Math.hypot(pt[0]-x,(pt[1]-y)*(p.height/p.width))<r;});});
    if(before!==mine.strokes.length) redraw(p);
  }

  function bind(p){
    var current=null;
    p.layer.addEventListener('pointerdown',function(e){
      if(!cfg.editable || tool==='hand') return;
      e.preventDefault();
      var q=rel(p,e),x=q[0],y=q[1];
      if(tool==='note'){post({type:'noteNew',page:p.n,x:x,y:y}); return;}
      if(tool==='paraf'){
        if(!cfg.canParaf){post({type:'toast',message:'Paraf hanya untuk verifikator'}); return;}
        snapshot(); mine.parafs.push({page:p.n,x:Math.min(x,.86),y:Math.min(y,.93),w:.14,h:.065}); redraw(p); changed(); return;
      }
      try{p.layer.setPointerCapture(e.pointerId);}catch(_){}
      snapshot();
      if(tool==='eraser'){current={erase:true}; erase(p,x,y); return;}
      var factor=tool==='highlighter'?4.5:1;
      current={page:p.n,tool:tool,color:(tool==='highlighter'&&color==='#e11d48')?'#facc15':color,size:Math.min(.1,Math.max(.0005,(size*factor)/p.width)),points:[[+x.toFixed(4),+y.toFixed(4)]]};
      mine.strokes.push(current);
    });
    p.layer.addEventListener('pointermove',function(e){
      if(!current) return; e.preventDefault();
      var evs=e.getCoalescedEvents?e.getCoalescedEvents():[e]; if(!evs.length) evs=[e];
      evs.forEach(function(ev){var q=rel(p,ev); if(current.erase) erase(p,q[0],q[1]); else current.points.push([+q[0].toFixed(4),+q[1].toFixed(4)]);});
      if(!current.erase) redraw(p);
    });
    var end=function(){if(current){ if(!current.erase && current.points.length>4000) current.points=current.points.slice(0,4000); current=null; changed(); }};
    p.layer.addEventListener('pointerup',end); p.layer.addEventListener('pointercancel',end);
  }

  function build(){
    var my=++buildId;
    root.innerHTML='<div class="sk"></div><div class="sk"></div>'; pages=[];
    if(!window.pdfjsLib){root.innerHTML='<div class="msg">Pustaka PDF gagal dimuat. Periksa koneksi internet.</div>'; post({type:'error',message:'pdfjs'}); return;}
    var W=root.clientWidth-24; lastW=root.clientWidth;
    window.pdfjsLib.getDocument({url:cfg.src}).promise.then(async function(pdf){
      for(var i=1;i<=pdf.numPages;i++){
        if(my!==buildId) return;
        var page=await pdf.getPage(i);
        var vp1=page.getViewport({scale:1}), scale=W/vp1.width, ratio=Math.min(window.devicePixelRatio||1,2);
        var vp=page.getViewport({scale:scale*ratio});
        var wrap=document.createElement('div'); wrap.className='page';
        var pc=document.createElement('canvas'); pc.className='pdf'; pc.width=vp.width; pc.height=vp.height; pc.style.height=(vp.height/ratio)+'px';
        wrap.appendChild(pc);
        var layer=document.createElement('div'); layer.className='layer';
        var c=document.createElement('canvas'); c.width=vp.width; c.height=vp.height; layer.appendChild(c); wrap.appendChild(layer);
        var pg=document.createElement('div'); pg.className='pg'; pg.textContent=i+' / '+pdf.numPages; wrap.appendChild(pg);
        if(i===1) root.innerHTML='';
        root.appendChild(wrap);
        await page.render({canvasContext:pc.getContext('2d'),viewport:vp}).promise;
        var p={n:i,wrap:wrap,layer:layer,canvas:c,ctx:c.getContext('2d'),width:W,height:vp.height/ratio};
        p.ctx.scale(ratio,ratio); bind(p); pages.push(p); redraw(p);
        if(i===1) post({type:'firstPage'});
      }
      setTool(tool);
      post({type:'ready',pages:pdf.numPages});
    }).catch(function(e){
      if(my!==buildId) return;
      root.innerHTML='<div class="msg">Dokumen gagal dimuat.</div>';
      post({type:'error',message:String((e&&e.message)||e)});
    });
  }

  window.__cmd=function(c){
    switch(c.type){
      case 'init': cfg=c; mine=norm(c.mine); others=(c.others||[]).map(function(o){return {author:o.author,data:norm(o.data)};}); undo=[];
        document.body.classList.toggle('compact',!!c.compact); tool=c.tool||'hand'; build(); break;
      case 'annotations': mine=norm(c.mine); others=(c.others||[]).map(function(o){return {author:o.author,data:norm(o.data)};}); undo=[]; redrawAll(); break;
      case 'config': cfg.editable=c.editable; cfg.canParaf=c.canParaf; cfg.parafUrl=c.parafUrl; setTool(tool); redrawAll(); break;
      case 'tool': setTool(c.tool); break;
      case 'color': color=c.color; break;
      case 'size': size=c.size; break;
      case 'undo': if(undo.length){mine=JSON.parse(undo.pop()); redrawAll(); changed();} break;
      case 'clear': snapshot(); mine=empty(); redrawAll(); changed(); break;
      case 'addNote': snapshot(); mine.notes.push({page:c.page,x:c.x,y:c.y,text:c.text}); redrawAll(); changed(); break;
      case 'editNote': if(mine.notes[c.index]){snapshot(); mine.notes[c.index].text=c.text; redrawAll(); changed();} break;
      case 'deleteNote': if(mine.notes[c.index]){snapshot(); mine.notes.splice(c.index,1); redrawAll(); changed();} break;
      case 'scrollToPage': if(pages[c.page-1]) pages[c.page-1].wrap.scrollIntoView({behavior:'smooth',block:'start'}); break;
    }
  };
  window.addEventListener('message',function(e){var d=e.data; if(d&&d.__pdfcmd) window.__cmd(d.cmd);});
  var rt; window.addEventListener('resize',function(){clearTimeout(rt); rt=setTimeout(function(){if(cfg && Math.abs(root.clientWidth-lastW)>40) build();},400);});
  post({type:'boot'});
})();
</script></body></html>`;

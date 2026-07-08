(function($){
  function activateTab($tab){
    var target = $tab.data('tab');
    $('.df-tab').removeClass('is-active');
    $('.df-tab-panel').removeClass('is-active');
    $tab.addClass('is-active');
    $('#df-tab-' + target).addClass('is-active');
  }
  function applyPageFilters(){
    var q = String($('[data-df-page-search]').val() || '').toLowerCase();
    var filter = $('.df-filter-pills button.is-active').data('df-filter') || 'all';
    var visible = 0;
    $('[data-df-page-card]').each(function(){
      var $card = $(this);
      var titleMatch = String($card.data('title') || '').indexOf(q) !== -1;
      var filterMatch = filter === 'all'
        || (filter === 'diviforge' && String($card.data('diviforge')) === '1')
        || (filter === 'with-thumb' && String($card.data('thumb')) === '1')
        || String($card.data('status')) === filter;
      var show = titleMatch && filterMatch;
      $card.toggleClass('hidden-by-filter', !show);
      if (show) visible++;
    });
    $('[data-df-result-count]').text(visible);
  }

  function escapeHtml(str){
    return String(str || '').replace(/[&<>"']/g,function(ch){return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[ch];});
  }
  function buildPreviewDocument(previewHtml, previewCss){
    var baseCss = `
      html,body{margin:0;padding:0;background:#0b1020;color:#101828;font-family:Inter,Arial,sans-serif;}
      body{padding:22px;box-sizing:border-box;}
      .dfpkg-preview-shell{max-width:1120px;margin:0 auto;background:#f7f8fc;border-radius:24px;overflow:hidden;box-shadow:0 24px 70px rgba(0,0,0,.28);}
      .dfpkg-preview-bar{display:flex;gap:7px;align-items:center;height:34px;padding:0 14px;background:#111827;color:#fff;}
      .dfpkg-preview-bar span{width:9px;height:9px;border-radius:99px;background:#fff;opacity:.35;}
      .dfpkg-preview-content{background:#fff;min-height:640px;}
      .dfpkg-section{position:relative;padding:54px 44px;border-bottom:1px solid rgba(15,23,42,.08);background:#fff;}
      .dfpkg-section:nth-child(even){background:#f8fafc;}
      .dfpkg-section-label{display:inline-flex;margin-bottom:14px;padding:5px 10px;border-radius:999px;background:#eef2ff;color:#4f46e5;font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.08em;}
      .dfpkg-row{display:grid;grid-template-columns:repeat(var(--dfpkg-cols,1),minmax(0,1fr));gap:28px;align-items:center;max-width:1080px;margin:0 auto;}
      .dfpkg-column{display:grid;gap:14px;}
      .dfpkg-module h1{font-size:clamp(32px,5vw,58px);line-height:1.02;margin:0 0 12px;color:#101828;letter-spacing:-.05em;}
      .dfpkg-module h2{font-size:clamp(26px,3vw,42px);line-height:1.08;margin:0 0 10px;color:#101828;letter-spacing:-.04em;}
      .dfpkg-module h3{font-size:22px;margin:0 0 8px;color:#101828;}
      .dfpkg-module p{font-size:16px;line-height:1.68;margin:0 0 10px;color:#475467;}
      .dfpkg-button-module a{display:inline-flex;align-items:center;justify-content:center;min-height:42px;padding:0 18px;border-radius:999px;background:#4f46e5;color:#fff;text-decoration:none;font-weight:800;box-shadow:0 12px 24px rgba(79,70,229,.24);}
      .dfpkg-image-module img{display:block;max-width:100%;height:auto;border-radius:20px;box-shadow:0 18px 45px rgba(16,24,40,.14);}
      .dfpkg-image-placeholder{display:flex;align-items:center;justify-content:center;min-height:220px;border-radius:22px;background:linear-gradient(135deg,#eef2ff,#f8fafc);color:#667085;font-weight:800;border:1px dashed #c7d2fe;}
      @media(max-width:780px){body{padding:12px}.dfpkg-section{padding:38px 22px}.dfpkg-row{grid-template-columns:1fr!important}.dfpkg-module h1{font-size:34px}.dfpkg-module h2{font-size:28px}}
    `;
    return '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><style>'+baseCss+'\n'+String(previewCss || '')+'</style></head><body><div class="dfpkg-preview-shell"><div class="dfpkg-preview-bar"><span></span><span></span><span></span></div><main class="dfpkg-preview-content">'+(previewHtml || '<section class="dfpkg-section"><div class="dfpkg-row"><div class="dfpkg-column"><div class="dfpkg-module"><h2>No preview available</h2><p>The package could be inspected, but no visual layout could be generated.</p></div></div></div></section>')+'</main></div></body></html>';
  }

  $(function(){
    $('.df-tab').first().each(function(){ activateTab($(this)); });
    $(document).on('click','.df-tab',function(){ activateTab($(this)); });
    $(document).on('input','[data-df-page-search]',applyPageFilters);
    $(document).on('click','.df-filter-pills button',function(e){ e.preventDefault(); $('.df-filter-pills button').removeClass('is-active'); $(this).addClass('is-active'); applyPageFilters(); });
    $(document).on('click','[data-df-view]',function(e){
      e.preventDefault();
      var view = $(this).data('df-view');
      $('[data-df-view]').removeClass('is-active');
      $(this).addClass('is-active');
      $('.df-page-card-grid').toggleClass('is-compact', view === 'compact');
      try { window.localStorage.setItem('diviforge_pages_view', view); } catch(err) {}
    });
    try {
      var savedView = window.localStorage.getItem('diviforge_pages_view');
      if (savedView === 'compact') { $('[data-df-view="compact"]').trigger('click'); }
    } catch(err) {}
    applyPageFilters();
    $(document).on('click','.df-copy-prompt',function(e){
      e.preventDefault();
      var text = $(this).closest('.df-card').find('textarea').val();
      navigator.clipboard.writeText(text).then(function(){ alert(DiviForgeAdmin.copied); });
    });
    $(document).on('click','[data-df-preview-size]',function(e){
      e.preventDefault();
      var size = $(this).data('df-preview-size');
      $('[data-df-preview-size]').removeClass('is-active');
      $(this).addClass('is-active');
      $('[data-df-preview-stage]').removeClass('is-desktop is-tablet is-mobile').addClass('is-'+size);
    });
    $('#df-inspector-form').on('submit',function(e){
      e.preventDefault();
      var formData = new FormData(this);
      formData.append('action','diviforge_inspect_package');
      formData.append('nonce',DiviForgeAdmin.nonce);
      $('#df-inspector-result').html('<div class="df-card df-inspecting"><span class="dashicons dashicons-update"></span><strong>Inspecting package...</strong></div>');
      $.ajax({url:DiviForgeAdmin.ajaxUrl,type:'POST',data:formData,processData:false,contentType:false})
      .done(function(resp){
        if(!resp.success){ $('#df-inspector-result').html('<div class="df-card df-error"><strong>Error:</strong> '+ resp.data.message +'</div>'); return; }
        var d = resp.data;
        var score = Number(d.readiness || 0);
        var scoreClass = score >= 85 ? 'df-score-good' : (score >= 60 ? 'df-score-warn' : 'df-score-bad');
        var manifest = d.manifest_summary || {};
        var moduleTypes = d.module_types || {};
        var moduleHtml = Object.keys(moduleTypes).length
          ? Object.keys(moduleTypes).map(function(k){ return '<span class="df-pill"><b>'+moduleTypes[k]+'</b> '+k+'</span>'; }).join('')
          : '<span class="df-muted">No module types detected</span>';
        var manifestHtml = Object.keys(manifest).length
          ? Object.keys(manifest).map(function(k){
              var val = Array.isArray(manifest[k]) ? manifest[k].join(', ') : manifest[k];
              return '<span><b>'+k+'</b>'+val+'</span>';
            }).join('')
          : '<span class="df-muted">No manifest metadata available</span>';
        var html = '<div class="df-inspector-report">';
        html += '<div class="df-card df-inspector-score"><div><p class="df-kicker">Package Inspector</p><h2>'+d.title+'</h2><p>'+d.filename+'</p></div><div class="df-score-ring '+scoreClass+'"><strong>'+score+'</strong><span>readiness</span></div></div>';
        html += '<div class="df-inspector-grid">';
        html += '<div class="df-card"><h3>Required files</h3><div class="df-inspector-files">';
        html += '<span class="df-pill '+(d.has_manifest?'df-ok':'df-warn')+'">manifest.json</span>';
        html += '<span class="df-pill '+(d.has_layout?'df-ok':'df-error')+'">layout.json</span>';
        html += '<span class="df-pill '+(d.has_css?'df-ok':'df-warn')+'">page.css</span>';
        html += '<span class="df-pill '+(d.compatible?'df-ok':'df-error')+'">'+(d.compatible?'Divi compatible':'Compatibility warning')+'</span>';
        html += '</div></div>';
        html += '<div class="df-card"><h3>Package stats</h3><div class="df-package-stats df-inspector-stats">';
        html += '<span><b>'+d.stats.sections+'</b>sections</span><span><b>'+d.stats.rows+'</b>rows</span><span><b>'+d.stats.columns+'</b>columns</span><span><b>'+d.stats.modules+'</b>modules</span><span><b>'+d.stats.images+'</b>images</span><span><b>'+d.stats.css_lines+'</b>css lines</span></div></div>';
        html += '</div>';
        html += '<div class="df-inspector-grid">';
        html += '<div class="df-card"><h3>Manifest metadata</h3><div class="df-manifest-list">'+manifestHtml+'</div></div>';
        html += '<div class="df-card"><h3>Module types</h3><p class="df-pill-list">'+moduleHtml+'</p></div>';
        html += '</div>';
        html += '<div class="df-card"><h3>Sections</h3><p class="df-pill-list">'+(d.sections.length ? d.sections.map(function(s){return '<span class="df-pill">'+s+'</span>';}).join('') : '<span class="df-muted">No sections found</span>')+'</p></div>';
        html += '<div class="df-card"><h3>Assets</h3><p class="df-pill-list">'+(d.images.length ? d.images.slice(0,20).map(function(s){return '<span class="df-pill">'+s+'</span>';}).join('') : '<span class="df-muted">No images found</span>')+'</p>';
        if(d.images.length > 20){ html += '<p class="df-muted">+'+(d.images.length-20)+' more images</p>'; }
        html += '</div>';
        if(d.warnings.length){ html += '<div class="df-card df-inspector-warnings"><h3>Warnings</h3><ul>'+d.warnings.map(function(w){return '<li>'+w+'</li>';}).join('')+'</ul></div>'; }
        else { html += '<div class="df-card df-inspector-ok"><h3>No blocking warnings</h3><p>This package looks ready for import.</p></div>'; }
        html += '<div class="df-card df-preview-card"><div class="df-preview-head"><div><p class="df-kicker">Package Preview</p><h3>Lightweight visual preview</h3><p>This preview approximates the package layout before import. Final rendering still happens in Divi.</p></div><div class="df-preview-toggle"><button class="is-active" data-df-preview-size="desktop">Desktop</button><button data-df-preview-size="tablet">Tablet</button><button data-df-preview-size="mobile">Mobile</button></div></div><div class="df-preview-stage is-desktop" data-df-preview-stage><iframe title="DiviForge package preview"></iframe></div></div>';
        html += '<div class="df-card df-next-step-card"><h3>Next step</h3><p>Als de preview klopt, ga je naar Pages om een nieuwe pagina te maken of een bestaande pagina te updaten met dit package.</p><a class="df-btn df-btn-primary" href="admin.php?page=diviforge-pages"><span class="dashicons dashicons-media-document"></span>Open Pages</a></div>';
        html += '</div>';
        $('#df-inspector-result').html(html);
        var iframe = $('#df-inspector-result iframe')[0];
        if (iframe) {
          iframe.srcdoc = buildPreviewDocument(d.preview_html || '', d.preview_css || '');
        }
      })
      .fail(function(){ $('#df-inspector-result').html('<div class="df-card df-error">The package could not be inspected.</div>'); });
    });
  });
})(jQuery);

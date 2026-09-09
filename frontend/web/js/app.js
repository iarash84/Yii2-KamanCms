(function () {
    'use strict';

    const siteHeader = document.querySelector('.site-header');
    if (siteHeader) {
        const updateHeader = function () { siteHeader.classList.toggle('is-scrolled', window.scrollY > 12); };
        window.addEventListener('scroll', updateHeader, {passive: true});
        updateHeader();
    }

    const root = document.documentElement;
    const themeOptions = Array.from(document.querySelectorAll('[data-theme-option]'));
    const themeMedia = window.matchMedia('(prefers-color-scheme: dark)');
    const allowedThemes = ['system', 'site-light', 'site-dark'];
    const legacyThemes = {light: 'site-light', dark: 'site-dark'};
    const applyTheme = function (preference, persist) {
        preference = legacyThemes[preference] || preference;
        if (!allowedThemes.includes(preference)) preference = 'system';
        const resolved = preference === 'system' ? (themeMedia.matches ? 'site-dark' : 'site-light') : preference;
        root.dataset.theme = resolved;
        root.dataset.themePreference = preference;
        document.querySelectorAll('[data-theme-current-icon]').forEach(function (icon) {
            icon.dataset.activeTheme = resolved;
        });
        themeOptions.forEach(function (option) {
            option.setAttribute('aria-pressed', option.dataset.themeOption === preference ? 'true' : 'false');
        });
        if (persist) localStorage.setItem('color-theme', preference);
    };
    applyTheme(localStorage.getItem('color-theme') || 'system', false);
    themeOptions.forEach(function (option) {
        option.addEventListener('click', function () {
            applyTheme(option.dataset.themeOption, true);
            const details = option.closest('details');
            if (details) details.open = false;
        });
    });
    const handleSystemTheme = function () { if (root.dataset.themePreference === 'system') applyTheme('system', false); };
    if (themeMedia.addEventListener) themeMedia.addEventListener('change', handleSystemTheme);
    else themeMedia.addListener(handleSystemTheme);

    const toggle = document.querySelector('[data-nav-toggle]');
    const nav = document.querySelector('[data-primary-nav]');
    if (toggle && nav) {
        toggle.addEventListener('click', function () {
            const open = nav.classList.toggle('is-open');
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            document.body.classList.toggle('is-nav-open', open);
        });
        nav.addEventListener('click', function (event) {
            if (event.target.closest('a') && window.matchMedia('(max-width: 64rem)').matches) {
                nav.classList.remove('is-open');
                toggle.setAttribute('aria-expanded', 'false');
                document.body.classList.remove('is-nav-open');
            }
        });
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && nav.classList.contains('is-open')) {
                nav.classList.remove('is-open');
                toggle.setAttribute('aria-expanded', 'false');
                document.body.classList.remove('is-nav-open');
                toggle.focus();
            }
        });
    }

    document.querySelectorAll('.content-shell form .form-control, .content-shell form input, .content-shell form select, .content-shell form textarea').forEach(function (field) {
        if (field.type === 'hidden' || field.type === 'submit' || field.dataset.richEditor !== undefined) return;
        if (field.type === 'checkbox') field.classList.add('d-checkbox');
        else if (field.type === 'radio') field.classList.add('d-radio');
        else if (field.type === 'file') field.classList.add('d-file-input', 'd-file-input-bordered');
        else if (field.tagName === 'SELECT') field.classList.add('d-select', 'd-select-bordered');
        else if (field.tagName === 'TEXTAREA') field.classList.add('d-textarea', 'd-textarea-bordered');
        else field.classList.add('d-input', 'd-input-bordered');
    });

    const adminSidebar = document.querySelector('[data-admin-sidebar]');
    const adminSidebarToggle = document.querySelector('[data-admin-sidebar-toggle]');
    if (adminSidebar && adminSidebarToggle) {
        const setSidebar = function (open) {
            adminSidebar.classList.toggle('is-open', open);
            adminSidebarToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        };
        adminSidebarToggle.addEventListener('click', function () { setSidebar(!adminSidebar.classList.contains('is-open')); });
        adminSidebar.addEventListener('click', function (event) {
            if (event.target.closest('a') && window.matchMedia('(max-width: 64rem)').matches) setSidebar(false);
        });
        document.addEventListener('keydown', function (event) { if (event.key === 'Escape') setSidebar(false); });
    }

    document.querySelectorAll('[data-admin-nav-group]').forEach(function (group) {
        group.addEventListener('toggle', function () {
            if (!group.open) return;
            document.querySelectorAll('[data-admin-nav-group][open]').forEach(function (otherGroup) {
                if (otherGroup !== group) otherGroup.open = false;
            });
        });
    });

    document.querySelectorAll('.admin-content form .form-control, .admin-content form input, .admin-content form select, .admin-content form textarea').forEach(function (field) {
        if (field.type === 'hidden' || field.type === 'submit') return;
        if (field.type === 'checkbox') field.classList.add('d-toggle', 'd-toggle-sm');
        else if (field.type === 'radio') field.classList.add('d-radio');
        else if (field.type === 'file') field.classList.add('d-file-input', 'd-file-input-bordered');
        else if (field.tagName === 'SELECT') field.classList.add('d-select', 'd-select-bordered');
        else if (field.tagName === 'TEXTAREA') field.classList.add('d-textarea', 'd-textarea-bordered');
        else field.classList.add('d-input', 'd-input-bordered');
    });
    document.querySelectorAll('.admin-content .btn').forEach(function (button) {
        button.classList.add('d-btn');
        if (button.classList.contains('btn-danger')) button.classList.add('d-btn-error');
        else if (button.classList.contains('btn-secondary')) button.classList.add('d-btn-outline');
        else button.classList.add('d-btn-primary');
    });

    document.addEventListener('change', function (event) {
        const input = event.target.closest('[data-avatar-input]');
        if (!input) return;
        const file = input.files && input.files[0];
        const panel = input.closest('.user-avatar-panel');
        const preview = panel ? panel.querySelector('[data-avatar-preview]') : null;
        if (!file || !preview) return;
        if (file.type && !file.type.startsWith('image/')) return;

        const reader = new FileReader();
        reader.addEventListener('load', function () {
            const image = document.createElement('img');
            image.alt = '';
            image.src = String(reader.result);
            preview.replaceChildren(image);
        });
        reader.addEventListener('error', function () {
            input.value = '';
        });
        reader.readAsDataURL(file);
    });

    const confirmationDialog = document.querySelector('[data-confirmation-dialog]');
    if (confirmationDialog) {
        let pendingConfirmation = null;
        document.addEventListener('click', function (event) {
            const trigger = event.target.closest('[data-confirm]');
            if (!trigger || trigger.dataset.confirmApproved === 'true') return;
            event.preventDefault();
            event.stopImmediatePropagation();
            pendingConfirmation = trigger;
            confirmationDialog.querySelector('[data-confirmation-message]').textContent = trigger.dataset.confirm;
            confirmationDialog.returnValue = 'cancel';
            confirmationDialog.showModal();
        }, true);
        confirmationDialog.addEventListener('close', function () {
            if (confirmationDialog.returnValue === 'confirm' && pendingConfirmation) {
                const trigger = pendingConfirmation;
                pendingConfirmation = null;
                trigger.dataset.confirmApproved = 'true';
                const confirmationMessage = trigger.getAttribute('data-confirm');
                trigger.removeAttribute('data-confirm');
                trigger.click();
                delete trigger.dataset.confirmApproved;
                window.setTimeout(function () {
                    if (confirmationMessage) trigger.setAttribute('data-confirm', confirmationMessage);
                }, 0);
            } else {
                pendingConfirmation = null;
            }
        });
    }

    const imagePreviewDialog = document.querySelector('[data-image-preview-dialog]');
    if (imagePreviewDialog) {
        const previewImage = imagePreviewDialog.querySelector('[data-image-preview-target]');
        document.addEventListener('click', function (event) {
            const trigger = event.target.closest('[data-image-preview]');
            if (!trigger) return;
            previewImage.src = trigger.dataset.imagePreview;
            previewImage.alt = trigger.dataset.imageAlt || '';
            imagePreviewDialog.showModal();
        });
        imagePreviewDialog.addEventListener('close', function () { previewImage.src = ''; });
    }

    const remoteDetailDialog = document.querySelector('[data-remote-detail-dialog]');
    if (remoteDetailDialog) {
        const remoteContent = remoteDetailDialog.querySelector('[data-remote-detail-content]');
        document.addEventListener('click', function (event) {
            const trigger = event.target.closest('[data-remote-dialog-url]');
            if (!trigger) return;
            remoteContent.innerHTML = '<span class="d-loading d-loading-spinner" aria-label="Loading"></span>';
            remoteDetailDialog.showModal();
            fetch(trigger.dataset.remoteDialogUrl, {headers: {'X-Requested-With': 'XMLHttpRequest'}}).then(function (response) { if (!response.ok) throw new Error(); return response.text(); }).then(function (html) { remoteContent.innerHTML = html; }).catch(function () { remoteContent.textContent = trigger.dataset.errorMessage || 'Unable to load details.'; });
        });
        remoteDetailDialog.addEventListener('close', function () { remoteContent.textContent = ''; });
    }

    document.querySelectorAll('[data-dismiss-alert]').forEach(function (button) {
        button.addEventListener('click', function () {
            button.closest('[role="alert"], [role="status"]').remove();
        });
    });

    const setFormLoading = function (form) {
        const submit = form.querySelector('[type="submit"]');
        if (!submit || submit.disabled || form.dataset.submitting === 'true') return false;
        form.dataset.submitting = 'true';
        submit.dataset.loading = 'true';
        submit.classList.add('is-loading');
        submit.setAttribute('aria-busy', 'true');
        submit.setAttribute('aria-disabled', 'true');
        const spinner = document.createElement('span');
        spinner.className = 'd-loading d-loading-spinner d-loading-sm';
        spinner.dataset.submitSpinner = 'true';
        spinner.setAttribute('aria-hidden', 'true');
        submit.prepend(spinner);
        return true;
    };
    const resetFormLoading = function (form) {
        const submit = form.querySelector('[type="submit"][data-loading="true"]');
        if (!submit) return;
        delete form.dataset.submitting;
        submit.classList.remove('is-loading');
        submit.removeAttribute('aria-busy');
        submit.removeAttribute('aria-disabled');
        delete submit.dataset.loading;
        const spinner = submit.querySelector('[data-submit-spinner]');
        if (spinner) spinner.remove();
    };
    const resetDownloadFormLoading = function (form) {
        if (form.dataset.downloadForm !== undefined) {
            window.setTimeout(function () { resetFormLoading(form); }, 500);
        }
    };
    document.querySelectorAll('form').forEach(function (form) {
        if (form.method === 'dialog' || form.getAttribute('method') === 'dialog') return;
        form.addEventListener('submit', function (event) {
            if (window.jQuery && window.jQuery(form).data('yiiActiveForm')) return;
            if (!setFormLoading(form)) {
                event.preventDefault();
                return;
            }
            resetDownloadFormLoading(form);
            window.setTimeout(function () {
                if (event.defaultPrevented) resetFormLoading(form);
            }, 0);
        });
    });
    if (window.jQuery) {
        window.jQuery(document).on('beforeSubmit', 'form', function (event) {
            const form = event.currentTarget;
            if (!setFormLoading(form)) {
                event.preventDefault();
                return false;
            }
            resetDownloadFormLoading(form);
            window.setTimeout(function () {
                if (event.isDefaultPrevented()) resetFormLoading(form);
            }, 0);
        });
    }

    const scrollButton = document.getElementById('scroll-to-top');
    if (scrollButton) {
        const update = function () {
            scrollButton.classList.toggle('is-visible', window.scrollY > 320);
        };
        window.addEventListener('scroll', update, {passive: true});
        scrollButton.addEventListener('click', function () {
            window.scrollTo({top: 0, behavior: 'smooth'});
        });
        update();
    }

    const heroSlider = document.querySelector('[data-hero-slider]');
    if (heroSlider) {
        const slides = Array.from(heroSlider.querySelectorAll('[data-hero-slide]'));
        const dots = Array.from(heroSlider.querySelectorAll('[data-hero-dot]'));
        let activeSlide = 0;
        const showSlide = function (index) {
            activeSlide = (index + slides.length) % slides.length;
            slides.forEach(function (slide, slideIndex) {
                const active = slideIndex === activeSlide;
                slide.classList.toggle('is-active', active);
                slide.setAttribute('aria-hidden', active ? 'false' : 'true');
                slide.toggleAttribute('inert', !active);
            });
            dots.forEach(function (dot, dotIndex) {
                dot.setAttribute('aria-current', dotIndex === activeSlide ? 'true' : 'false');
            });
        };
        const moveSlide = function (offset) { showSlide(activeSlide + offset); };
        const previous = heroSlider.querySelector('[data-hero-previous]');
        const next = heroSlider.querySelector('[data-hero-next]');
        if (previous) previous.addEventListener('click', function () { moveSlide(-1); });
        if (next) next.addEventListener('click', function () { moveSlide(1); });
        dots.forEach(function (dot) { dot.addEventListener('click', function () { showSlide(Number(dot.dataset.heroDot)); }); });
        showSlide(0);
    }

    const dashboard = document.querySelector('[data-dashboard-widgets]');
    if (dashboard) {
        let layout = JSON.parse(dashboard.dataset.layout || '{"order":[],"hidden":[]}');
        const picker = document.querySelector('[data-dashboard-picker]');
        const widgets = Array.from(dashboard.querySelectorAll('[data-widget]'));
        const savedHiddenWidgets = layout.hidden || [];
        let recoveredEmptyDashboard = false;
        if (widgets.length > 0 && widgets.every(function (widget) {
            return savedHiddenWidgets.includes(widget.dataset.widget);
        })) {
            layout.hidden = [];
            recoveredEmptyDashboard = true;
        }
        const save = function () {
            layout.order = Array.from(dashboard.querySelectorAll('[data-widget]')).map(el => el.dataset.widget);
            layout.hidden = widgets.filter(el => el.hidden).map(el => el.dataset.widget);
            layout.collapsed = widgets.filter(el => el.classList.contains('is-collapsed')).map(el => el.dataset.widget);
            layout.quick_links = Array.from(dashboard.querySelectorAll('[data-quick-link]:checked')).map(el => el.dataset.quickLink);
            const body = new URLSearchParams(); body.set('layout', JSON.stringify(layout)); body.set(dashboard.dataset.csrfParam, dashboard.dataset.csrfToken);
            fetch(dashboard.dataset.saveUrl, {method: 'POST', headers: {'Content-Type':'application/x-www-form-urlencoded;charset=UTF-8'}, body: body.toString()});
        };
        layout.order.forEach(id => { const el=dashboard.querySelector('[data-widget="'+id+'"]'); if(el) dashboard.appendChild(el); });
        widgets.forEach(function (el) {
            el.hidden = (layout.hidden || []).includes(el.dataset.widget);
            el.classList.toggle('is-collapsed', (layout.collapsed || []).includes(el.dataset.widget));

            const toolbar = document.createElement('div');
            toolbar.className = 'dashboard-widget-toolbar';
            const title = document.createElement('strong');
            title.textContent = el.dataset.title;
            const collapse = document.createElement('button');
            collapse.type = 'button';
            collapse.className = 'd-btn d-btn-sm d-btn-square d-btn-ghost dashboard-widget-toggle';
            collapse.innerHTML = '<svg class="icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"></path></svg>';
            const updateCollapse = function () {
                const collapsed = el.classList.contains('is-collapsed');
                collapse.setAttribute('aria-label', collapsed ? dashboard.dataset.expandLabel : dashboard.dataset.collapseLabel);
                collapse.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
            };
            updateCollapse();
            collapse.addEventListener('click', function () {
                el.classList.toggle('is-collapsed');
                updateCollapse();
                save();
            });
            toolbar.append(title, collapse);
            el.prepend(toolbar);

            const label = document.createElement('label');
            const input = document.createElement('input');
            input.type = 'checkbox';
            input.className = 'd-toggle d-toggle-sm';
            input.checked = !el.hidden;
            input.addEventListener('change', function () { el.hidden = !input.checked; save(); });
            label.append(input, document.createTextNode(' ' + el.dataset.title));
            picker.appendChild(label);
            el.addEventListener('dragstart', function () { el.classList.add('is-dragging'); });
            el.addEventListener('dragend', function () { el.classList.remove('is-dragging'); save(); });
        });
        if (recoveredEmptyDashboard) save();
        dashboard.addEventListener('dragover', event => { event.preventDefault(); const moving=dashboard.querySelector('.is-dragging'); const target=event.target.closest('[data-widget]'); if(moving && target && moving!==target) dashboard.insertBefore(moving, target); });
        dashboard.querySelectorAll('[data-quick-link]').forEach(function (input) {
            input.addEventListener('change', function () {
                const action = dashboard.querySelector('[data-quick-action="' + input.dataset.quickLink + '"]');
                if (action) {
                    action.hidden = !input.checked;
                    action.classList.toggle('is-hidden', !input.checked);
                }
                save();
            });
        });
        const customize=document.querySelector('[data-dashboard-customize]'); if(customize) customize.addEventListener('click',()=>{picker.hidden=!picker.hidden;});
    }

    document.querySelectorAll('textarea[data-rich-editor]').forEach(function (source) {
        const shell = document.createElement('div'); shell.className = 'rich-editor';
        const toolbar = document.createElement('div'); toolbar.className = 'rich-editor-toolbar'; toolbar.setAttribute('role', 'toolbar');
        const editor = document.createElement('div'); editor.className = 'rich-editor-content'; editor.contentEditable = 'true'; editor.dir = source.closest('[dir]')?.getAttribute('dir') || 'auto'; editor.innerHTML = source.value;
        const commands = [['bold','B'],['italic','I'],['formatBlock','H2'],['insertUnorderedList','•'],['insertOrderedList','1.'],['removeFormat','×']];
        commands.forEach(function (item) { const button=document.createElement('button'); button.type='button'; button.className='rich-editor-button'; button.textContent=item[1]; button.title=item[0]; button.addEventListener('click',function(){ editor.focus(); document.execCommand(item[0], false, item[0]==='formatBlock' ? 'h2' : null); editor.dispatchEvent(new Event('input')); }); toolbar.appendChild(button); });
        const link=document.createElement('button'); link.type='button'; link.className='rich-editor-button'; link.textContent='↗'; link.title='Link'; link.addEventListener('click',function(){ const url=window.prompt('URL'); if(url && /^(https?:\/\/|\/|#|mailto:)/i.test(url)){editor.focus(); document.execCommand('createLink',false,url); editor.dispatchEvent(new Event('input'));} }); toolbar.appendChild(link);
        editor.addEventListener('input', function(){ source.value=editor.innerHTML; source.dispatchEvent(new Event('change',{bubbles:true})); });
        source.classList.add('rich-text-source-enhanced'); source.insertAdjacentElement('beforebegin', shell); shell.append(toolbar, editor);
    });

    const sectionSorter = document.querySelector('[data-home-section-sorter]');
    if (sectionSorter) {
        const state = document.querySelector('[data-home-section-save-state]');
        let moving = null;
        const saveSections = function () {
            const items = Array.from(sectionSorter.querySelectorAll('[data-section-id]')).map(function(row){ return {id:Number(row.dataset.sectionId),enabled:row.querySelector('[data-section-enabled]').checked}; });
            const body=new URLSearchParams(); body.set('items',JSON.stringify(items)); body.set(sectionSorter.dataset.csrfParam,sectionSorter.dataset.csrfToken);
            state.textContent='…';
            fetch(sectionSorter.dataset.saveUrl,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded;charset=UTF-8'},body:body.toString()}).then(function(response){if(!response.ok) throw new Error(); return response.json();}).then(function(){state.textContent='✓';}).catch(function(){state.textContent='!';});
        };
        sectionSorter.querySelectorAll('[data-section-id]').forEach(function(row){ row.addEventListener('dragstart',function(){moving=row; row.classList.add('is-dragging');}); row.addEventListener('dragend',function(){row.classList.remove('is-dragging'); moving=null; saveSections();}); row.querySelector('[data-section-enabled]').addEventListener('change',saveSections); });
        sectionSorter.addEventListener('dragover',function(event){event.preventDefault(); const target=event.target.closest('[data-section-id]'); if(moving && target && target!==moving) sectionSorter.insertBefore(moving,target);});
    }

    const faqSorter = document.querySelector('[data-faq-sorter]');
    if (faqSorter) {
        let movingFaq = null;
        const saveFaqOrder = function () {
            const body = new URLSearchParams();
            body.set('ids', JSON.stringify(Array.from(faqSorter.querySelectorAll('[data-faq-id]')).map(row => Number(row.dataset.faqId))));
            body.set(faqSorter.dataset.csrfParam, faqSorter.dataset.csrfToken);
            fetch(faqSorter.dataset.saveUrl, {method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'}, body: body.toString()});
        };
        faqSorter.querySelectorAll('[data-faq-id]').forEach(function (row) {
            row.addEventListener('dragstart', function () { movingFaq = row; row.classList.add('is-dragging'); });
            row.addEventListener('dragend', function () { row.classList.remove('is-dragging'); movingFaq = null; saveFaqOrder(); });
        });
        faqSorter.addEventListener('dragover', function (event) { event.preventDefault(); const target = event.target.closest('[data-faq-id]'); if (movingFaq && target && target !== movingFaq) faqSorter.insertBefore(movingFaq, target); });
    }

    const carouselSorter = document.querySelector('[data-carousel-sorter]');
    if (carouselSorter) {
        const state = document.querySelector('[data-carousel-save-state]');
        let movingSlide = null;
        const saveCarouselOrder = function () {
            const body = new URLSearchParams();
            body.set('ids', JSON.stringify(Array.from(carouselSorter.querySelectorAll('[data-carousel-id]')).map(row => Number(row.dataset.carouselId))));
            body.set(carouselSorter.dataset.csrfParam, carouselSorter.dataset.csrfToken);
            if (state) state.textContent = '…';
            fetch(carouselSorter.dataset.saveUrl, {method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'}, body: body.toString()})
                .then(function (response) { if (!response.ok) throw new Error(); return response.json(); })
                .then(function () { if (state) state.textContent = '✓'; })
                .catch(function () { if (state) state.textContent = '!'; });
        };
        carouselSorter.querySelectorAll('[data-carousel-id]').forEach(function (row) {
            row.addEventListener('dragstart', function () { movingSlide = row; row.classList.add('is-dragging'); });
            row.addEventListener('dragend', function () { row.classList.remove('is-dragging'); movingSlide = null; saveCarouselOrder(); });
        });
        carouselSorter.addEventListener('dragover', function (event) {
            event.preventDefault();
            const target = event.target.closest('[data-carousel-id]');
            if (movingSlide && target && target !== movingSlide) carouselSorter.insertBefore(movingSlide, target);
        });
    }

    document.querySelectorAll('[data-admin-tabs]').forEach(function (tabs) {
        const buttons = Array.from(tabs.querySelectorAll('[data-tab-target]'));
        const panels = Array.from(tabs.querySelectorAll('[data-tab-panel]'));
        buttons.forEach(function (button) {
            button.addEventListener('click', function () {
                buttons.forEach(function (item) { const active = item === button; item.classList.toggle('d-tab-active', active); item.setAttribute('aria-selected', active ? 'true' : 'false'); });
                panels.forEach(function (panel) { panel.hidden = panel.id !== button.dataset.tabTarget; });
            });
        });
    });
}());

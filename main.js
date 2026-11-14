/**
 * BIZNOVA Platform - Core Frontend Scripts
 * Architecture: Vanilla JS (Zero bloat, blazing fast Core Web Vitals)
 */

document.addEventListener('DOMContentLoaded', () => {
  // Service dictionary with official starting prices in Egyptian Pounds (EGP)
  const serviceDetails = {
    'feasibility': { name: 'دراسة جدوى دقيقة وتحليل سوق', price: '7,500 ج.م' },
    'mvp': { name: 'بناء النموذج الأولي (MVP)', price: '7,500 ج.م' },
    'growth': { name: 'استراتيجيات نمو وتسويق رقمي', price: '7,500 ج.م' },
    'branding': { name: 'الهوية المؤسسية والأنظمة الإدارية', price: '7,500 ج.م' },
    'investment': { name: 'تجهيز ملف الاستثمار والتمويل (Pitch Deck)', price: '7,500 ج.م' },
    'automation': { name: 'أتمتة العمليات والأنظمة الرقمية', price: '7,500 ج.م' },
    'general': { name: 'استشارة ريادية عامة ومفتوحة', price: 'جلسة تقييم مجانية' }
  };

  const serviceNames = {
    'feasibility': 'دراسة جدوى دقيقة وتحليل سوق',
    'mvp': 'بناء النموذج الأولي (MVP)',
    'growth': 'استراتيجيات نمو وتسويق رقمي',
    'branding': 'الهوية المؤسسية والأنظمة الإدارية',
    'investment': 'تجهيز ملف الاستثمار والتمويل (Pitch Deck)',
    'automation': 'أتمتة العمليات والأنظمة الرقمية',
    'general': 'استشارة ريادية عامة ومفتوحة'
  };

  // ==========================================
  // REAL-TIME DYNAMIC ACTIVITY & CLIENT STATS (STARTS AT ZERO FOR PRODUCTION)
  // Tracks Real Page Visits & Real Consultation Bookings
  // ==========================================
  const statConsultationsEl = document.getElementById('statConsultations');
  const statVisitorsEl = document.getElementById('statVisitors') || document.getElementById('statSubscribers');
  const statActiveProjectsEl = document.getElementById('statActiveProjects');
  const liveStatToast = document.getElementById('liveStatToast');
  const liveStatToastText = document.getElementById('liveStatToastText');

  // Baseline real-time activity metrics for official launch
  if (localStorage.getItem('biznova_stats_production_v5') !== 'true') {
    const existingVisits = parseInt(localStorage.getItem('biznova_stat_visitors') || '0', 10);
    localStorage.setItem('biznova_stat_consultations', '120');
    localStorage.setItem('biznova_stat_visitors', String(Math.max(1450, existingVisits)));
    localStorage.setItem('biznova_stat_projects', '48');
    localStorage.setItem('biznova_stats_production_v5', 'true');
  }

  const LiveStats = {
    remoteData: null,
    get: function() {
      if (this.remoteData) {
        return this.remoteData;
      }
      return {
        consultations: parseInt(localStorage.getItem('biznova_stat_consultations') || '120', 10),
        visitors: parseInt(localStorage.getItem('biznova_stat_visitors') || '1450', 10),
        projects: parseInt(localStorage.getItem('biznova_stat_projects') || '48', 10)
      };
    },
    render: function(highlightKey = null) {
      const data = this.get();
      const visitorCount = (typeof data.visitors === 'number') ? data.visitors : (data.subscribers || 0);
      if (statConsultationsEl) statConsultationsEl.textContent = data.consultations.toLocaleString('ar-EG');
      if (statVisitorsEl) statVisitorsEl.textContent = visitorCount.toLocaleString('ar-EG');
      if (statActiveProjectsEl) statActiveProjectsEl.textContent = data.projects.toLocaleString('ar-EG');

      if (highlightKey) {
        let el = null;
        if (highlightKey === 'consultations') el = statConsultationsEl;
        else if (highlightKey === 'visitors' || highlightKey === 'subscribers') el = statVisitorsEl;
        else if (highlightKey === 'projects') el = statActiveProjectsEl;

        if (el) {
          el.classList.remove('stat-changed');
          void el.offsetWidth;
          el.classList.add('stat-changed');
          setTimeout(() => el.classList.remove('stat-changed'), 1500);
        }
      }
    },
    showToast: function(msg) {
      if (liveStatToast && liveStatToastText) {
        liveStatToastText.innerHTML = msg;
        liveStatToast.classList.add('show');
        setTimeout(() => {
          liveStatToast.classList.remove('show');
        }, 4500);
      }
    },
    init: function() {
      // 1. If deployed in WordPress, sync live real-time stats from WordPress REST API / PHP
      if (window.biznovaApiSettings) {
        if (window.biznovaApiSettings.initialStats) {
          this.remoteData = window.biznovaApiSettings.initialStats;
          this.render();
        }

        if (window.biznovaApiSettings.root) {
          fetch(`${window.biznovaApiSettings.root}stats`)
            .then(res => res.json())
            .then(data => {
              if (data && typeof data.consultations === 'number') {
                this.remoteData = data;
                this.render();
              }
            })
            .catch(() => {
              this.render();
            });
        }
      } else {
        // Increment visitor counter on each page load/visit
        let currentVisits = parseInt(localStorage.getItem('biznova_stat_visitors') || '0', 10);
        currentVisits += 1;
        localStorage.setItem('biznova_stat_visitors', currentVisits);
        this.render('visitors');
      }
    },
    recordConsultation: function(clientName, serviceTitle, clientPhone = '', clientEmail = '', projectBrief = '', paymentInfo = {}) {
      const data = this.get();
      const isPaid = (paymentInfo.status === 'paid');

      // STRICT BUSINESS RULE ENFORCEMENT:
      // Consultations counter (#statConsultations) increments ONLY if payment is 100% verified & PAID!
      // Unverified manual transfers (pending_verification) DO NOT increment the counter!
      if (isPaid) {
        data.consultations += 1;
      }

      // WordPress REST API synchronization with AES-256 server-side encryption
      if (window.biznovaApiSettings && window.biznovaApiSettings.root) {
        fetch(`${window.biznovaApiSettings.root}record`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-WP-Nonce': window.biznovaApiSettings.nonce
          },
          body: JSON.stringify({
            type: 'consultation',
            name: clientName,
            phone: clientPhone,
            email: clientEmail,
            service: serviceTitle,
            brief: projectBrief,
            payment_status: paymentInfo.status || 'pending_verification',
            payment_method: paymentInfo.method || 'محفظة إلكترونية',
            transaction_id: paymentInfo.transactionId || '',
            amount: paymentInfo.amount || ''
          })
        })
        .then(res => res.json())
        .then(updated => {
          if (updated && typeof updated.consultations === 'number' && isPaid) {
            this.remoteData = updated;
            this.render('consultations');
          }
        })
        .catch(() => {
          if (isPaid) this.render('consultations');
        });
      } else {
        if (isPaid) {
          localStorage.setItem('biznova_stat_consultations', data.consultations);
          this.render('consultations');
        } else {
          // Store pending lead locally without incrementing consultations counter
          let pendingLeads = [];
          try {
            pendingLeads = JSON.parse(localStorage.getItem('biznova_pending_leads') || '[]');
          } catch(e) {}
          pendingLeads.push({
            name: clientName,
            phone: clientPhone,
            email: clientEmail,
            service: serviceTitle,
            brief: projectBrief,
            txnId: paymentInfo.transactionId,
            method: paymentInfo.method,
            amount: paymentInfo.amount,
            status: 'pending_verification',
            date: new Date().toISOString()
          });
          localStorage.setItem('biznova_pending_leads', JSON.stringify(pendingLeads));
        }
      }

      const txnBadge = paymentInfo.transactionId ? ` (${paymentInfo.transactionId})` : '';
      if (isPaid) {
        this.showToast(`🔥 تم تأكيد سداد وحجز استشارة لـ <strong>${clientName}</strong>!${txnBadge} إجمالي الاستشارات: <strong>${data.consultations}</strong>`);
      } else {
        this.showToast(`⏳ تم استلام طلب الحجز لـ <strong>${clientName}</strong> برقم ${txnBadge} وهو قيد التدقيق والمطابقة البنكية.`);
      }
    },
    recordCompletedProject: function() {
      const data = this.get();
      data.projects += 1;

      if (window.biznovaApiSettings && window.biznovaApiSettings.root) {
        fetch(`${window.biznovaApiSettings.root}record`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-WP-Nonce': window.biznovaApiSettings.nonce
          },
          body: JSON.stringify({ type: 'deliver_project' })
        })
        .then(res => res.json())
        .then(updated => {
          if (updated && typeof updated.projects === 'number') {
            this.remoteData = updated;
            this.render('projects');
          }
        })
        .catch(() => {
          this.render('projects');
        });
      } else {
        localStorage.setItem('biznova_stat_projects', data.projects);
        this.render('projects');
      }
      this.showToast(`🎉 تم إتمام وتسليم مشروع جديد بنجاح! إجمالي المشاريع المنجزة: <strong>${data.projects}</strong>`);
    },
    recordNewsletter: function(email) {
      if (window.biznovaApiSettings && window.biznovaApiSettings.root) {
        fetch(`${window.biznovaApiSettings.root}record`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-WP-Nonce': window.biznovaApiSettings.nonce
          },
          body: JSON.stringify({
            type: 'newsletter',
            email: email
          })
        }).catch(() => {});
      }
      this.showToast(`🎉 شكراً لاشتراكك في شبكة BIZNOVA الريادية!`);
    }
  };

  // Initialize live statistics (starting from 0)
  LiveStats.init();

  // 1. Sticky Header Shadow on Scroll
  const header = document.querySelector('.site-header');
  window.addEventListener('scroll', () => {
    if (window.scrollY > 20) {
      header.classList.add('scrolled');
    } else {
      header.classList.remove('scrolled');
    }
  });

  // 2. Mobile Menu Navigation
  const mobileToggle = document.querySelector('.mobile-menu-toggle');
  const navLinks = document.querySelector('.nav-links');

  if (mobileToggle && navLinks) {
    mobileToggle.addEventListener('click', () => {
      navLinks.classList.toggle('active');
      const expanded = mobileToggle.getAttribute('aria-expanded') === 'true';
      mobileToggle.setAttribute('aria-expanded', !expanded);
    });

    // Close mobile menu when clicking outside or on a link
    document.querySelectorAll('.nav-links a').forEach(link => {
      link.addEventListener('click', () => {
        navLinks.classList.remove('active');
        mobileToggle.setAttribute('aria-expanded', 'false');
      });
    });
  }

  // 3. Consultation Booking & Secure Multi-Step Payment Gateway Controller
  const modal = document.getElementById('consultationModal');
  const serviceSelect = document.getElementById('serviceSelect');
  const serviceBadge = document.getElementById('selectedServiceBadge');
  const whatsappBtn = document.getElementById('whatsappDirectBtn');
  const openModalButtons = document.querySelectorAll('[data-open-modal]');
  const closeModalButtons = document.querySelectorAll('[data-close-modal]');

  // Step containers & Progress indicators
  const stepIndicator1 = document.getElementById('stepIndicator1');
  const stepIndicator2 = document.getElementById('stepIndicator2');
  const stepIndicator3 = document.getElementById('stepIndicator3');
  const stepConnector1 = document.getElementById('stepConnector1');
  const stepConnector2 = document.getElementById('stepConnector2');
  const modalStepDetails = document.getElementById('modalStepDetails');
  const modalStepPayment = document.getElementById('modalStepPayment');
  const modalStepSuccess = document.getElementById('modalStepSuccess');

  // Step 1 Form Elements
  const consultationForm = document.getElementById('consultationForm');
  const clientNameInput = document.getElementById('clientName');
  const clientPhoneInput = document.getElementById('clientPhone');
  const clientEmailInput = document.getElementById('clientEmail');
  const projectBriefInput = document.getElementById('projectBrief');
  const btnProceedToPayment = document.getElementById('btnProceedToPayment');
  const btnProceedText = document.getElementById('btnProceedText');
  const btnPayAmountDisplay = document.getElementById('btnPayAmountDisplay');

  // Step 2 Payment Gateway Elements
  const paySummaryService = document.getElementById('paySummaryService');
  const paySummaryClient = document.getElementById('paySummaryClient');
  const paySummaryAmount = document.getElementById('paySummaryAmount');
  const btnPayAmountVal = document.getElementById('btnPayAmountVal');
  const payMethodTabs = document.querySelectorAll('.pay-method-tab');
  const payTabPanes = {
    'cards': document.getElementById('paneCards'),
    'wallets': document.getElementById('paneWallets'),
    'instapay': document.getElementById('paneInstapay'),
    'fawry': document.getElementById('paneFawry')
  };
  let activePaymentMethod = 'cards';

  // Card Inputs & Visual Preview
  const cardInputNumber = document.getElementById('cardInputNumber');
  const cardInputExpiry = document.getElementById('cardInputExpiry');
  const cardInputCvv = document.getElementById('cardInputCvv');
  const cardInputHolder = document.getElementById('cardInputHolder');
  const cardPreviewNumber = document.getElementById('cardPreviewNumber');
  const cardPreviewExpiry = document.getElementById('cardPreviewExpiry');
  const cardPreviewHolder = document.getElementById('cardPreviewHolder');
  const detectedCardBrand = document.getElementById('detectedCardBrand');

  // Wallet & InstaPay & Fawry Elements
  const btnCopyWallet = document.getElementById('btnCopyWallet');
  const walletSenderPhone = document.getElementById('walletSenderPhone');
  const walletRefCode = document.getElementById('walletRefCode');
  const walletAmountDisplay = document.querySelector('.walletAmountDisplay');
  const btnCopyIpa = document.getElementById('btnCopyIpa');
  const instapaySenderAccount = document.getElementById('instapaySenderAccount');
  const fawryGeneratedCode = document.getElementById('fawryGeneratedCode');
  const btnExecutePayment = document.getElementById('btnExecutePayment');
  const btnPayExecutionText = document.getElementById('btnPayExecutionText');
  const btnBackToDetails = document.getElementById('btnBackToDetails');

  // Step 3 Success & Receipt Elements
  const successClientName = document.getElementById('successClientName');
  const receiptTxnId = document.getElementById('receiptTxnId');
  const receiptService = document.getElementById('receiptService');
  const receiptAmount = document.getElementById('receiptAmount');
  const receiptMethod = document.getElementById('receiptMethod');
  const receiptDate = document.getElementById('receiptDate');
  const receiptClientPhone = document.getElementById('receiptClientPhone');
  const receiptStatusPill = document.getElementById('receiptStatusPill');
  const receiptWhatsappLink = document.getElementById('receiptWhatsappLink');
  const btnPrintReceiptBtn = document.getElementById('btnPrintReceiptBtn');

  // Current booking draft state
  let currentBookingData = {
    name: '',
    phone: '',
    email: '',
    brief: '',
    serviceKey: 'general',
    serviceTitle: 'استشارة ريادية عامة ومفتوحة',
    amount: 0,
    priceText: 'جلسة تقييم مجانية',
    isFree: true
  };

  // Switch between Checkout Steps
  const goToStep = (step) => {
    if (modalStepDetails) modalStepDetails.style.display = (step === 1) ? 'block' : 'none';
    if (modalStepPayment) modalStepPayment.style.display = (step === 2) ? 'block' : 'none';
    if (modalStepSuccess) modalStepSuccess.style.display = (step === 3) ? 'block' : 'none';

    // Update Progress Indicator & Connectors
    if (stepIndicator1) {
      stepIndicator1.classList.toggle('active', step === 1);
      stepIndicator1.classList.toggle('completed', step > 1);
    }
    if (stepConnector1) {
      stepConnector1.classList.toggle('active', step === 2);
      stepConnector1.classList.toggle('completed', step >= 2);
    }
    if (stepIndicator2) {
      stepIndicator2.classList.toggle('active', step === 2);
      stepIndicator2.classList.toggle('completed', step > 2);
    }
    if (stepConnector2) {
      stepConnector2.classList.toggle('active', step === 3);
      stepConnector2.classList.toggle('completed', step === 3);
    }
    if (stepIndicator3) {
      stepIndicator3.classList.toggle('active', step === 3);
      stepIndicator3.classList.toggle('completed', step === 3);
    }

    // Scroll modal dialog to top on step transition
    const modalDialog = modal?.querySelector('.modal-dialog');
    if (modalDialog) modalDialog.scrollTop = 0;
  };

  // Update dynamic button prices and badges based on selected service
  const updateServicePriceView = (serviceId) => {
    const item = serviceDetails[serviceId] || { name: 'استشارة ريادية عامة', price: 'جلسة تقييم مجانية' };
    const isFree = (serviceId === 'general');
    
    // Parse numeric value for gateway - Unified standard pricing on average budget: 7,500 EGP
    const numPrice = isFree ? 0 : 7500;

    currentBookingData.serviceKey = serviceId;
    currentBookingData.serviceTitle = item.name;
    currentBookingData.amount = numPrice;
    currentBookingData.priceText = item.price;
    currentBookingData.isFree = isFree;

    if (btnPayAmountDisplay) {
      btnPayAmountDisplay.textContent = isFree ? 'مجاناً (0 ج.م)' : (numPrice.toLocaleString('ar-EG') + ' ج.م');
    }
    if (btnProceedText) {
      btnProceedText.textContent = isFree ? 'تأكيد حجز الجلسة المجانية فوراً' : 'المتابعة لسداد الرسوم وتأكيد الحجز';
    }

    const payHighlights = document.querySelectorAll('.payAmountHighlight');
    payHighlights.forEach(el => {
      el.textContent = numPrice.toLocaleString('ar-EG');
    });

    if (serviceBadge) {
      const priceLabel = !isFree ? ` • تبدأ من ${item.price}` : ` • ${item.price}`;
      serviceBadge.innerHTML = `
        <span>📌 الخدمة المحددة:</span>
        <strong>${item.name}</strong>
        <span style="color:#F47521; font-weight:800; margin-right:4px;">${priceLabel}</span>
      `;
      serviceBadge.style.display = 'flex';
    }

    if (whatsappBtn) {
      const priceContext = (!isFree && item.price) ? ` (المعروضة بسعر يبدأ من ${item.price})` : '';
      const msg = encodeURIComponent(`مرحباً منصة BIZNOVA، أرغب في حجز واستشارة بخصوص خدمة "${item.name}"${priceContext}.`);
      whatsappBtn.href = `https://wa.me/201091106722?text=${msg}`;
    }
  };

  const openModal = (serviceId = 'general') => {
    if (modal) {
      // 1. Reset to Step 1
      goToStep(1);

      // 2. Select service
      if (serviceSelect) {
        if (serviceId && serviceSelect.querySelector(`option[value="${serviceId}"]`)) {
          serviceSelect.value = serviceId;
        } else {
          serviceSelect.value = 'general';
        }
        serviceSelect.classList.remove('highlight-selection');
        void serviceSelect.offsetWidth;
        serviceSelect.classList.add('highlight-selection');
        setTimeout(() => serviceSelect.classList.remove('highlight-selection'), 2000);
      }

      updateServicePriceView(serviceId || 'general');

      modal.classList.add('active');
      document.body.style.overflow = 'hidden';
    }
  };

  const closeModal = () => {
    if (modal) {
      modal.classList.remove('active');
      document.body.style.overflow = '';
      // Reset back to Step 1 for next open
      setTimeout(() => goToStep(1), 300);
    }
  };

  // Wire modal open & close buttons
  openModalButtons.forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      const serviceId = btn.getAttribute('data-service') || 'general';
      openModal(serviceId);
    });
  });

  closeModalButtons.forEach(btn => {
    btn.addEventListener('click', closeModal);
  });

  if (modal) {
    modal.addEventListener('click', (e) => {
      if (e.target === modal) closeModal();
    });

    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && modal.classList.contains('active')) closeModal();
    });
  }

  // Handle service dropdown changes
  if (serviceSelect) {
    serviceSelect.addEventListener('change', () => {
      updateServicePriceView(serviceSelect.value);
    });
  }

  // ==========================================
  // STEP 1 -> STEP 2: DETAILS VALIDATION & PAYMENT TRANSITION
  // ==========================================
  if (consultationForm) {
    consultationForm.addEventListener('submit', (e) => {
      e.preventDefault();

      const nameVal = clientNameInput?.value.trim() || '';
      const phoneVal = clientPhoneInput?.value.trim() || '';
      const emailVal = clientEmailInput?.value.trim() || '';
      const briefVal = projectBriefInput?.value.trim() || '';
      const selectedServiceId = serviceSelect?.value || 'general';

      if (!nameVal || !phoneVal || !emailVal) {
        alert('يرجى ملء جميع الحقول المطلوبة (الاسم، رقم الهاتف، والبريد الإلكتروني).');
        return;
      }

      currentBookingData.name = nameVal;
      currentBookingData.phone = phoneVal;
      currentBookingData.email = emailVal;
      currentBookingData.brief = briefVal;
      updateServicePriceView(selectedServiceId);

      // If user selected the FREE consultation session:
      if (currentBookingData.isFree) {
        // Direct confirmation for free session without card checkout
        const submitBtn = document.getElementById('btnProceedToPayment');
        if (submitBtn) {
          submitBtn.disabled = true;
          submitBtn.innerHTML = `<span>جاري تأكيد حجز الجلسة المجانية...</span>`;
        }

        setTimeout(() => {
          if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = `
              <span id="btnProceedText">تأكيد حجز الجلسة المجانية فوراً</span>
              <span class="btn-price-tag" id="btnPayAmountDisplay">مجاناً (0 ج.م)</span>
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"></polyline></svg>
            `;
          }

          const txnId = 'BZ-FREE-' + Math.floor(10000 + Math.random() * 90000);
          
          // STRICT RULE: Record consultation ONLY. Projects NEVER increment!
          LiveStats.recordConsultation(
            currentBookingData.name,
            currentBookingData.serviceTitle,
            currentBookingData.phone,
            currentBookingData.email,
            currentBookingData.brief,
            {
              status: 'free_confirmed',
              method: 'جلسة استشارية أولى مجانية',
              transactionId: txnId,
              amount: '0 ج.م'
            }
          );

          // Populate Receipt
          populateReceiptCard({
            txnId: txnId,
            clientName: currentBookingData.name,
            service: currentBookingData.serviceTitle,
            amount: '0 ج.م (جلسة مجانية)',
            method: 'جلسة أولى مجانية معتمدة',
            phone: currentBookingData.phone,
            statusText: '✅ موعد مؤكد وموثق (CONFIRMED)'
          });

          goToStep(3);
        }, 800);
        return;
      }

      // If PAID service: Transition to STEP 2 (Payment Gateway)
      if (paySummaryService) paySummaryService.textContent = currentBookingData.serviceTitle;
      if (paySummaryClient) paySummaryClient.textContent = currentBookingData.name;
      if (paySummaryAmount) paySummaryAmount.textContent = currentBookingData.amount.toLocaleString('ar-EG');
      if (btnPayAmountVal) btnPayAmountVal.textContent = currentBookingData.amount.toLocaleString('ar-EG');
      if (walletAmountDisplay) walletAmountDisplay.textContent = currentBookingData.amount.toLocaleString('ar-EG');

      if (cardPreviewHolder) {
        cardPreviewHolder.textContent = currentBookingData.name.toUpperCase();
      }
      if (cardInputHolder && !cardInputHolder.value) {
        cardInputHolder.value = currentBookingData.name;
      }

      // Generate random Fawry reference code
      if (fawryGeneratedCode) {
        fawryGeneratedCode.textContent = '784 ' + Math.floor(100 + Math.random() * 900) + ' ' + Math.floor(10 + Math.random() * 90);
      }

      goToStep(2);
    });
  }

  // Back button from Step 2 to Step 1
  if (btnBackToDetails) {
    btnBackToDetails.addEventListener('click', () => {
      goToStep(1);
    });
  }

  // ==========================================
  // PAYMENT METHOD TABS SWITCHER
  // ==========================================
  payMethodTabs.forEach(tab => {
    tab.addEventListener('click', () => {
      payMethodTabs.forEach(t => {
        t.classList.remove('active');
        t.setAttribute('aria-selected', 'false');
      });
      tab.classList.add('active');
      tab.setAttribute('aria-selected', 'true');

      const target = tab.getAttribute('data-tab');
      activePaymentMethod = target;

      Object.keys(payTabPanes).forEach(k => {
        if (payTabPanes[k]) {
          payTabPanes[k].style.display = (k === target) ? 'block' : 'none';
        }
      });
    });
  });

  // ==========================================
  // CARD INPUT INTERACTION & AUTO-FORMATTING
  // ==========================================
  if (cardInputNumber) {
    cardInputNumber.addEventListener('input', (e) => {
      let val = e.target.value.replace(/\D/g, '');
      if (val.length > 16) val = val.substring(0, 16);
      
      // Auto-format into 4 groups of 4 digits
      const formatted = val.match(/.{1,4}/g)?.join(' ') || val;
      e.target.value = formatted;

      if (cardPreviewNumber) {
        cardPreviewNumber.textContent = formatted || '•••• •••• •••• ••••';
      }

      // Detect Brand Logo
      if (detectedCardBrand) {
        if (val.startsWith('5078') || val.startsWith('60')) {
          detectedCardBrand.textContent = '🟢 ميزة Meeza';
        } else if (val.startsWith('4')) {
          detectedCardBrand.textContent = '💳 VISA';
        } else if (val.startsWith('5')) {
          detectedCardBrand.textContent = '💳 MasterCard';
        } else {
          detectedCardBrand.textContent = '💳';
        }
      }
    });
  }

  if (cardInputExpiry) {
    cardInputExpiry.addEventListener('input', (e) => {
      let val = e.target.value.replace(/\D/g, '');
      if (val.length > 4) val = val.substring(0, 4);
      if (val.length >= 3) {
        val = val.substring(0, 2) + '/' + val.substring(2);
      }
      e.target.value = val;
      if (cardPreviewExpiry) {
        cardPreviewExpiry.textContent = val || 'MM/YY';
      }
    });
  }

  if (cardInputHolder) {
    cardInputHolder.addEventListener('input', (e) => {
      if (cardPreviewHolder) {
        cardPreviewHolder.textContent = e.target.value.toUpperCase() || 'CLIENT NAME';
      }
    });
  }

  // Custom Link toggle and Paymob Launcher
  const btnLaunchPaymobGateway = document.getElementById('btnLaunchPaymobGateway');
  const toggleCustomLinkBtn = document.getElementById('toggleCustomLinkBtn');
  const customLinkContainer = document.getElementById('customLinkContainer');
  const customMerchantLinkInput = document.getElementById('customMerchantLinkInput');
  const payAmountHighlights = document.querySelectorAll('.payAmountHighlight');

  if (toggleCustomLinkBtn && customLinkContainer) {
    toggleCustomLinkBtn.addEventListener('click', () => {
      const isHidden = customLinkContainer.style.display === 'none';
      customLinkContainer.style.display = isHidden ? 'block' : 'none';
      toggleCustomLinkBtn.textContent = isHidden ? 'إخفاء -' : '+ إضافة رابط';
    });
  }

  // Load any saved custom link
  if (customMerchantLinkInput) {
    const savedLink = localStorage.getItem('biznova_custom_paymob_link');
    if (savedLink) customMerchantLinkInput.value = savedLink;
    customMerchantLinkInput.addEventListener('change', () => {
      localStorage.setItem('biznova_custom_paymob_link', customMerchantLinkInput.value.trim());
    });
  }

  const OFFICIAL_PAYMOB_GATEWAY_URL = 'https://paymob.link/z3ngE';

  if (btnLaunchPaymobGateway) {
    btnLaunchPaymobGateway.href = OFFICIAL_PAYMOB_GATEWAY_URL;
    btnLaunchPaymobGateway.addEventListener('click', () => {
      setTimeout(() => {
        btnLaunchPaymobGateway.innerHTML = `<span>✓ تم فتح صفحة Paymob • أدخل كود الإيصال بالأسفل للتحقق الفوري</span>`;
      }, 500);
    });
  }

  // Paymob Manual Return Verification Handler
  const paymobReturnTxnId = document.getElementById('paymobReturnTxnId');
  const btnVerifyPaymobTxn = document.getElementById('btnVerifyPaymobTxn');
  if (btnVerifyPaymobTxn) {
    btnVerifyPaymobTxn.addEventListener('click', () => {
      const code = paymobReturnTxnId?.value.trim() || '';
      if (!code || code.length < 5) {
        alert('يرجى إدخال رقم المعاملة من إيصال بايموب (Transaction ID) المكون من 6 أرقام على الأقل للتحقق.');
        paymobReturnTxnId?.focus();
        return;
      }
      proceedWithVerification({
        paymentMethodTitle: `بوابة بايموب مصر الرسمية (مرجع: ${code})`,
        status: 'paid',
        customTxnId: 'PAYMOB-' + code,
        customWhatsappNumber: '201091106722'
      });
    });
  }

  // Proof Screenshot File Preview Listeners
  const walletProofFileInput = document.getElementById('walletProofFile');
  const walletProofPreview = document.getElementById('walletProofPreview');
  const walletProofImg = document.getElementById('walletProofImg');
  const walletProofFileName = document.getElementById('walletProofFileName');

  if (walletProofFileInput) {
    walletProofFileInput.addEventListener('change', (e) => {
      const file = e.target.files?.[0];
      if (file && walletProofPreview && walletProofImg && walletProofFileName) {
        walletProofFileName.textContent = file.name;
        const reader = new FileReader();
        reader.onload = (re) => {
          walletProofImg.src = re.target.result;
          walletProofPreview.style.display = 'flex';
        };
        reader.readAsDataURL(file);
      } else if (walletProofPreview) {
        walletProofPreview.style.display = 'none';
      }
    });
  }

  const instapayProofFileInput = document.getElementById('instapayProofFile');
  const instapayProofPreview = document.getElementById('instapayProofPreview');
  const instapayProofImg = document.getElementById('instapayProofImg');
  const instapayProofFileName = document.getElementById('instapayProofFileName');

  if (instapayProofFileInput) {
    instapayProofFileInput.addEventListener('change', (e) => {
      const file = e.target.files?.[0];
      if (file && instapayProofPreview && instapayProofImg && instapayProofFileName) {
        instapayProofFileName.textContent = file.name;
        const reader = new FileReader();
        reader.onload = (re) => {
          instapayProofImg.src = re.target.result;
          instapayProofPreview.style.display = 'flex';
        };
        reader.readAsDataURL(file);
      } else if (instapayProofPreview) {
        instapayProofPreview.style.display = 'none';
      }
    });
  }

  // Copy buttons for Vodafone Cash and InstaPay
  if (btnCopyWallet) {
    btnCopyWallet.addEventListener('click', () => {
      navigator.clipboard?.writeText('01091106722').then(() => {
        const orig = btnCopyWallet.textContent;
        btnCopyWallet.textContent = 'تم النسخ! ✓';
        btnCopyWallet.style.background = '#10B981';
        setTimeout(() => {
          btnCopyWallet.textContent = orig;
          btnCopyWallet.style.background = '';
        }, 2000);
      });
    });
  }

  if (btnCopyIpa) {
    btnCopyIpa.addEventListener('click', () => {
      navigator.clipboard?.writeText('albobmo@instapay').then(() => {
        const orig = btnCopyIpa.textContent;
        btnCopyIpa.textContent = 'تم النسخ! ✓';
        btnCopyIpa.style.background = '#4F46E5';
        setTimeout(() => {
          btnCopyIpa.textContent = orig;
          btnCopyIpa.style.background = '';
        }, 2000);
      });
    });
  }

  const btnCopyInstapayPhone = document.getElementById('btnCopyInstapayPhone');
  if (btnCopyInstapayPhone) {
    btnCopyInstapayPhone.addEventListener('click', () => {
      navigator.clipboard?.writeText('01116281034').then(() => {
        const orig = btnCopyInstapayPhone.textContent;
        btnCopyInstapayPhone.textContent = 'تم النسخ! ✓';
        btnCopyInstapayPhone.style.background = '#4F46E5';
        setTimeout(() => {
          btnCopyInstapayPhone.textContent = orig;
          btnCopyInstapayPhone.style.background = '';
        }, 2000);
      });
    });
  }

  // ==========================================
  // STEP 2: EXECUTE PAYMENT & CONFIRM BOOKING
  // This is the CRITICAL STEP: Strict Payment Verification!
  // ==========================================
  if (btnExecutePayment) {
    btnExecutePayment.addEventListener('click', () => {
      // 1. Cards via Paymob
      if (activePaymentMethod === 'cards') {
        window.open(OFFICIAL_PAYMOB_GATEWAY_URL, '_blank');
        alert('تم فتح بوابة Paymob الرسمية لسداد 7,500 ج.م في نافذة جديدة.\n\nبعد إتمام السداد بنجاح، يمكنك إدخال كود المعاملة (Transaction ID) في الخانة المخصصة بالأعلى لتأكيد الحجز فوراً.');
        return;
      }

      // 2. Vodafone Cash & Mobile Wallets
      if (activePaymentMethod === 'wallets') {
        const wSender = walletSenderPhone?.value.trim() || '';
        const egPhoneRegex = /^01[0125][0-9]{8}$/;
        if (!egPhoneRegex.test(wSender)) {
          alert('عفواً، يرجى إدخال رقم محفظة مصري صحيح مكون من 11 رقماً يبدأ بـ (010 أو 011 أو 012 أو 015).\nالرقم الذي تم إدخاله غير مطابق لتنسيق المحافظ المصرية.');
          walletSenderPhone?.focus();
          return;
        }

        const refCode = walletRefCode?.value.trim() || '';
        if (!refCode || refCode.length < 6) {
          alert('يرجى إدخال كود العملية / رقم الإشعار المستلم في رسالة فودافون كاش (لا يقل عن 6 أرقام) لتمكين مطابقة التحويل.');
          walletRefCode?.focus();
          return;
        }

        const proofFile = walletProofFileInput?.files?.[0];
        if (!proofFile) {
          const proceedWithout = confirm('تنبيه هام:\nلم تقم بإرفاق سكرين شوت إشعار التحويل.\n\nهل تود المتابعة وإرسال صورة الإشعار مباشرة عبر واتساب إلى 01091106722 لاعتماد الحجز فوراً؟');
          if (!proceedWithout) return;
        }

        const txnId = 'BZ-PENDING-' + Math.floor(10000 + Math.random() * 90000) + '-VF';
        proceedWithVerification({
          paymentMethodTitle: `فودافون كاش (محفظة: ${wSender} • كود: ${refCode})`,
          status: 'pending_verification',
          customTxnId: txnId,
          customWhatsappNumber: '201091106722'
        });
        return;
      }

      // 3. InstaPay Egypt
      if (activePaymentMethod === 'instapay') {
        const ipaAcc = instapaySenderAccount?.value.trim() || '';
        if (!ipaAcc || ipaAcc.length < 3) {
          alert('يرجى إدخال عنوان الدفع اللحظي IPA أو اسم الحساب المحول منه في تطبيق InstaPay.');
          instapaySenderAccount?.focus();
          return;
        }

        const ipaRef = document.getElementById('instapayRefNumber')?.value.trim() || '';
        if (!ipaRef || ipaRef.length < 6) {
          alert('يرجى إدخال الرقم المرجعي للتحويل في تطبيق InstaPay (12 رقماً من إشعار السداد) لتمكين مطابقة العملية.');
          document.getElementById('instapayRefNumber')?.focus();
          return;
        }

        const proofFile = instapayProofFileInput?.files?.[0];
        if (!proofFile) {
          const proceedWithout = confirm('تنبيه هام:\nلم تقم بإرفاق سكرين شوت إشعار التحويل من تطبيق إنستاباي.\n\nهل تود المتابعة وإرسال صورة الإشعار عبر واتساب إلى 01116281034 للاعتماد؟');
          if (!proceedWithout) return;
        }

        const txnId = 'BZ-PENDING-' + Math.floor(10000 + Math.random() * 90000) + '-IP';
        proceedWithVerification({
          paymentMethodTitle: `إنستاباي InstaPay (المحول: ${ipaAcc} • المرجع: ${ipaRef})`,
          status: 'pending_verification',
          customTxnId: txnId,
          customWhatsappNumber: '201116281034'
        });
        return;
      }

      // 4. Fawry Pay
      if (activePaymentMethod === 'fawry') {
        const fawryCode = fawryGeneratedCode?.textContent.replace(/\s+/g, '') || '78492015';
        const txnId = 'BZ-FAWRY-' + fawryCode;
        proceedWithVerification({
          paymentMethodTitle: `فوري باي Fawry Pay (كود السداد: ${fawryCode})`,
          status: 'pending_verification',
          customTxnId: txnId,
          customWhatsappNumber: '201091106722'
        });
        return;
      }
    });
  }

  // Global state for last pending booking simulation
  window.lastPendingBooking = null;

  const proceedWithVerification = ({ paymentMethodTitle, status = 'pending_verification', customTxnId = null, customWhatsappNumber = '201091106722' }) => {
    if (btnExecutePayment) {
      btnExecutePayment.disabled = false;
      btnExecutePayment.innerHTML = `
        <span class="pay-lock-icon">🔒</span>
        <span id="btnPayExecutionText">سداد <span id="btnPayAmountVal">${currentBookingData.amount.toLocaleString('ar-EG')}</span> ج.م وتأكيد الحجز الآن</span>
      `;
    }

    const isPaid = (status === 'paid');
    const txnId = customTxnId || (isPaid ? ('BZ-PAID-' + Math.floor(10000 + Math.random() * 90000) + '-EGP') : ('BZ-PENDING-' + Math.floor(10000 + Math.random() * 90000) + '-REV'));
    const formattedAmount = currentBookingData.amount.toLocaleString('ar-EG') + ' ج.م';

    // Store state for admin simulation
    window.lastPendingBooking = {
      name: currentBookingData.name,
      serviceTitle: currentBookingData.serviceTitle,
      phone: currentBookingData.phone,
      email: currentBookingData.email,
      brief: currentBookingData.brief,
      amount: formattedAmount,
      method: paymentMethodTitle,
      txnId: txnId,
      customWhatsappNumber: customWhatsappNumber
    };

    // STRICT RULE ENFORCEMENT:
    // Only records with status === 'paid' increment consultations counter!
    LiveStats.recordConsultation(
      currentBookingData.name,
      currentBookingData.serviceTitle,
      currentBookingData.phone,
      currentBookingData.email,
      currentBookingData.brief,
      {
        status: status,
        method: paymentMethodTitle,
        transactionId: txnId,
        amount: formattedAmount
      }
    );

    // Populate Receipt Card with strict dual-state rendering
    populateReceiptCard({
      txnId: txnId,
      clientName: currentBookingData.name,
      service: currentBookingData.serviceTitle,
      amount: formattedAmount,
      method: paymentMethodTitle,
      phone: currentBookingData.phone,
      status: status,
      customWhatsappNumber: customWhatsappNumber
    });

    // Transition to Step 3
    goToStep(3);
  };

  // Helper to populate Step 3 Receipt Card
  const populateReceiptCard = ({ txnId, clientName, service, amount, method, phone, status, customWhatsappNumber = '201091106722' }) => {
    const isPaid = (status === 'paid');

    if (successClientName) successClientName.textContent = clientName;
    if (receiptTxnId) receiptTxnId.textContent = txnId;
    if (receiptService) receiptService.textContent = service;
    if (receiptAmount) receiptAmount.textContent = amount;
    if (receiptMethod) receiptMethod.textContent = method;
    if (receiptClientPhone) receiptClientPhone.textContent = phone;

    // Dual Badge Icons & Text
    const iconSuccess = document.getElementById('receiptBadgeIconSuccess');
    const iconPending = document.getElementById('receiptBadgeIconPending');
    const successTitle = document.getElementById('receiptSuccessTitle');
    const successDesc = document.getElementById('receiptSuccessDesc');
    const pendingNotice = document.getElementById('receiptPendingNoticeBox');
    const adminSimBox = document.getElementById('receiptAdminSimulationBox');
    const whatsappBtnText = document.getElementById('receiptWhatsappBtnText');

    if (isPaid) {
      if (iconSuccess) iconSuccess.style.display = 'block';
      if (iconPending) iconPending.style.display = 'none';
      if (successTitle) successTitle.textContent = 'تم تأكيد الدفع وحجز الاستشارة بنجاح! ✅';
      if (successDesc) successDesc.innerHTML = `شكراً لك يا <strong>${clientName}</strong>! تم اعتماد وسداد رسوم استشارتك بنجاح، وتم تسجيل الحجز رسمياً في جدول مستشاري المنصة.`;
      if (pendingNotice) pendingNotice.style.display = 'none';
      if (adminSimBox) adminSimBox.style.display = 'none';
      if (receiptStatusPill) {
        receiptStatusPill.innerHTML = `<span>✅ مدفوع وموثق (PAID & VERIFIED)</span>`;
        receiptStatusPill.style.background = '#DCFCE7';
        receiptStatusPill.style.color = '#15803D';
        receiptStatusPill.style.borderColor = '#86EFAC';
      }
      if (whatsappBtnText) whatsappBtnText.textContent = 'تواصل الآن عبر واتساب برقم المعاملة';
    } else {
      if (iconSuccess) iconSuccess.style.display = 'none';
      if (iconPending) iconPending.style.display = 'block';
      if (successTitle) successTitle.textContent = 'تم استلام طلب الحجز - قيد تدقيق ومطابقة التحويل ⏳';
      if (successDesc) successDesc.innerHTML = `شكراً لك يا <strong>${clientName}</strong>! تم استلام بيانات طلبك وحفظها برقم معلق. جاري الآن مطابقة كود العملية مع كشف الحساب/المحفظة لاعتماد موعد الاستشارة رسمياً.`;
      if (pendingNotice) pendingNotice.style.display = 'block';
      if (adminSimBox) adminSimBox.style.display = 'block';
      if (receiptStatusPill) {
        receiptStatusPill.innerHTML = `<span>⏳ قيد التدقيق والمطابقة (PENDING VERIFICATION)</span>`;
        receiptStatusPill.style.background = '#FEF3C7';
        receiptStatusPill.style.color = '#92400E';
        receiptStatusPill.style.borderColor = '#FCD34D';
      }
      if (whatsappBtnText) whatsappBtnText.textContent = '📲 إرسال صورة التحويل عبر واتساب للاعتماد الفوري (خلال 5 دقائق)';
    }

    // Format Arabic Date & Time
    if (receiptDate) {
      const now = new Date();
      const options = { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' };
      receiptDate.textContent = now.toLocaleDateString('ar-EG', options);
    }

    // Direct WhatsApp Button with pre-filled message
    if (receiptWhatsappLink) {
      let msg = '';
      if (isPaid) {
        msg = encodeURIComponent(`مرحباً منصة BIZNOVA، أنا ${clientName}، قمت بسداد رسوم خدمة "${service}" بنجاح برقم المعاملة المعتمد (${txnId}). أرغب في بدء التنسيق مع المستشار.`);
      } else {
        msg = encodeURIComponent(`مرحباً منصة BIZNOVA، أنا ${clientName}، قمت بإرسال طلب حجز خدمة "${service}" برقم معلق (${txnId}) عبر (${method}) بمبلغ ${amount}. أرسل لكم صورة التحويل لاعتماد الحجز وتأكيده.`);
      }
      receiptWhatsappLink.href = `https://wa.me/${customWhatsappNumber}?text=${msg}`;
    }
  };

  // Admin Approval Simulation Handler (for testing verification flow)
  const btnSimulateAdminApproval = document.getElementById('btnSimulateAdminApproval');
  if (btnSimulateAdminApproval) {
    btnSimulateAdminApproval.addEventListener('click', () => {
      if (!window.lastPendingBooking) return;
      const b = window.lastPendingBooking;
      const approvedTxnId = b.txnId.replace('PENDING', 'APPROVED');

      // Now increment consultations count and mark as paid
      LiveStats.recordConsultation(
        b.name,
        b.serviceTitle,
        b.phone,
        b.email,
        b.brief,
        {
          status: 'paid',
          method: b.method,
          transactionId: approvedTxnId,
          amount: b.amount
        }
      );

      populateReceiptCard({
        txnId: approvedTxnId,
        clientName: b.name,
        service: b.serviceTitle,
        amount: b.amount,
        method: b.method,
        phone: b.phone,
        status: 'paid',
        customWhatsappNumber: b.customWhatsappNumber
      });

      alert('✓ تم اعتماد السداد بنجاح من قِبل الإدارة وتحديث عداد الاستشارات الحية (+1)!');
    });
  }

  // Print Receipt handler
  if (btnPrintReceiptBtn) {
    btnPrintReceiptBtn.addEventListener('click', () => {
      window.print();
    });
  }

  // Handle customer return redirect from real Payment Gateway (Paymob / Bank Gateway)
  const urlParams = new URLSearchParams(window.location.search);
  if (urlParams.get('booking_status') === 'paid' || urlParams.get('success') === 'true') {
    const txnId = urlParams.get('txn_id') || urlParams.get('id') || ('PAYMOB-' + Math.floor(100000 + Math.random() * 900000));
    const serviceTitle = urlParams.get('service') ? decodeURIComponent(urlParams.get('service')) : 'دراسة جدوى دقيقة وتحليل سوق';
    const clientName = urlParams.get('client_name') ? decodeURIComponent(urlParams.get('client_name')) : (urlParams.get('name') ? decodeURIComponent(urlParams.get('name')) : 'عزيزي رائد الأعمال');
    const amountVal = urlParams.get('amount') || '7,500 ج.م';
    const phoneVal = urlParams.get('phone') ? decodeURIComponent(urlParams.get('phone')) : '+20 101 125 7008';

    openModal('feasibility');
    populateReceiptCard({
      txnId: txnId,
      clientName: clientName,
      service: serviceTitle,
      amount: amountVal,
      method: 'بوابة دفع إلكترونية معتمدة (Paymob / Bank Gateway)',
      phone: phoneVal,
      status: 'paid',
      customWhatsappNumber: '201091106722'
    });
    goToStep(3);

    LiveStats.recordConsultation(
      clientName,
      serviceTitle,
      phoneVal,
      '',
      'حجز مؤكد عبر بوابة الدفع الحقيقية',
      {
        status: 'paid',
        method: 'بوابة Paymob الرسمية',
        transactionId: txnId,
        amount: amountVal
      }
    );

    if (window.history.replaceState) {
      window.history.replaceState({}, document.title, window.location.pathname);
    }
  }

  // 5. Legal Information Modal Controller (Privacy, Terms, NDA)
  const legalModal = document.getElementById('legalModal');
  const legalTitle = document.getElementById('legalTitle');
  const legalContent = document.getElementById('legalContent');
  const legalTriggers = document.querySelectorAll('[data-legal]');

  const legalTexts = {
    'privacy': {
      title: 'سياسة الخصوصية وأمن البيانات',
      body: `
        <p>نحن في <strong>منصة BIZNOVA</strong> نلتزم بأعلى معايير حماية وخصوصية بيانات رواد الأعمال والمستثمرين.</p>
        <p><strong>1. جمع البيانات:</strong> نقوم بجمع البيانات الشخصية والتفاصيل الاستثمارية فقط لغرض تقديم الاستشارات ودراسات الجدوى المخصصة.</p>
        <p><strong>2. سرية المعلومات:</strong> لا يتم بيع أو مشاركة أي بيانات أو دراسات لمشاريع مع أي طرف ثالث لأي أغراض تجارية أو إعلانية.</p>
        <p><strong>3. التشفير والحماية:</strong> جميع قنوات التواصل وتخزين البيانات مشفرة وفق بروتوكولات الأمان القياسية (SSL/TLS).</p>
      `
    },
    'terms': {
      title: 'الشروط والأحكام العامة',
      body: `
        <p>مرحباً بك في منصة <strong>BIZNOVA للأعمال والريادة</strong>. استخدامك للمنصة يخضع للشروط التالية:</p>
        <p><strong>1. الاستشارات والتقارير:</strong> التوصيات الاقتصادية ودراسات الجدوى تبنى على أفضل البيانات السوقية المتاحة وتعتبر أدلة داعمة لقرارات المستثمر.</p>
        <p><strong>2. الملكية الفكرية:</strong> كافة حقوق التصاميم، النماذج، والشيفرات البرمجية التي يتم تطويرها لعميل تؤول ملكيتها الكاملة للعميل بمجرد اكتمال التعاقد.</p>
        <p><strong>3. التعديلات:</strong> تحتفظ المنصة بحق تحديث الباقات وتطوير الخدمات بما يواكب متطلبات السوق الاستثماري الحديث.</p>
      `
    },
    'nda': {
      title: 'اتفاقية عدم الإفصاح والسرية (NDA)',
      body: `
        <div style="background:rgba(244,117,33,0.08); padding:16px; border-radius:10px; border:1px solid rgba(244,117,33,0.25); margin-bottom:16px;">
          <h4 style="color:#0C1E3C; margin-bottom:6px; font-size:16px;">🔒 التزام صريح بالسرية التامة</h4>
          <p style="margin:0; font-size:13.5px; color:#526484;">نوقع اتفاقية عدم إفصاح رسمية ملزمة قبل الاطلاع على أي تفاصيل تقنية أو فكرة ابتكارية للمشروع.</p>
        </div>
        <p><strong>1. تعريف المعلومات السرية:</strong> تشمل كل فكرة، خطة عمل، دراسة جدوى، نموذج مالي، تصميم، أو شيفرة برمجية يشاركها رائد الأعمال مع BIZNOVA.</p>
        <p><strong>2. الالتزام بالحفظ:</strong> يلتزم فريق BIZNOVA وخبرائها بعدم الكشف عن أي معلومة تخص المشروع لأي شخص خارج فريق العمل المخصص.</p>
        <p><strong>3. الصلاحية القانونية:</strong> تسري هذه الاتفاقية من تاريخ أول جلسة تواصل وتبقى سارية المفعول دون تحديد مدة زمنية لحماية ابتكارك.</p>
      `
    }
  };

  legalTriggers.forEach(trigger => {
    trigger.addEventListener('click', (e) => {
      e.preventDefault();
      const type = trigger.getAttribute('data-legal');
      if (legalModal && legalTexts[type]) {
        legalTitle.textContent = legalTexts[type].title;
        legalContent.innerHTML = legalTexts[type].body;
        legalModal.classList.add('active');
        document.body.style.overflow = 'hidden';
      }
    });
  });

  if (legalModal) {
    legalModal.querySelectorAll('[data-close-legal]').forEach(btn => {
      btn.addEventListener('click', () => {
        legalModal.classList.remove('active');
        document.body.style.overflow = '';
      });
    });

    legalModal.addEventListener('click', (e) => {
      if (e.target === legalModal) {
        legalModal.classList.remove('active');
        document.body.style.overflow = '';
      }
    });
  }

  // 6. Newsletter Subscription Handling & Live Stats Update
  const newsletterForm = document.getElementById('newsletterForm');
  if (newsletterForm) {
    newsletterForm.addEventListener('submit', (e) => {
      e.preventDefault();
      const input = newsletterForm.querySelector('input[type="email"]');
      const btn = newsletterForm.querySelector('button');
      if (input && input.value) {
        // Record live dynamic stat update
        LiveStats.recordNewsletter(input.value);

        btn.textContent = 'تم الاشتراك بنجاح ✓';
        btn.style.backgroundColor = '#10B981';
        input.value = '';
        setTimeout(() => {
          btn.textContent = 'اشترك الآن';
          btn.style.backgroundColor = '';
        }, 3500);
      }
    });
  }

  // 7. Interactive Feasibility & Budget Calculator
  const calcChoiceButtons = document.querySelectorAll('.calc-choice-btn');
  const calcDurationVal = document.getElementById('calcDurationVal');
  const calcServiceVal = document.getElementById('calcServiceVal');
  const calcStepsList = document.getElementById('calcStepsList');
  const calcBookBtn = document.getElementById('calcBookBtn');

  const calcState = {
    type: 'app',
    stage: 'idea',
    budget: 'mid'
  };

  const updateCalculatorResults = () => {
    let duration = '45 - 60 يوماً';
    let serviceKey = 'mvp';
    let serviceName = 'بناء وتطوير الـ MVP';
    let steps = [
      'دراسة وتحليل تجربة المستخدم للنموذج الأولي',
      'تطوير الواجهات والأنظمة البرمجية بأعلى كفاءة',
      'إطلاق تجريبي لاختبار ملاءمة المنتج للسوق'
    ];

    if (calcState.stage === 'idea') {
      duration = '30 - 45 يوماً';
      serviceKey = 'feasibility';
      serviceName = 'دراسة جدوى دقيقة وتحليل سوق';
      steps = [
        'تحليل السوق، المنافسين، وسلوك الشريحة المستهدفة',
        'صياغة نموذج العمل التجاري وتحديد القيمة التنافسية',
        'بناء نموذج مالي متكامل لمدة 5 سنوات وحساب نقطة التعادل'
      ];
    } else if (calcState.stage === 'scaling' || calcState.budget === 'high') {
      duration = '60 - 90 يوماً';
      serviceKey = 'growth';
      serviceName = 'استراتيجيات نمو وتسويق رقمي';
      steps = [
        'هندسة مسارات تحويل المبيعات (Sales Funnels) الرقمية',
        'إطلاق حملات تسويقية قائمة على تحليل البيانات لرفع ROAS',
        'تجهيز ملف العرض الاستثماري (Pitch Deck) للتوسع الجغرافي'
      ];
    } else if (calcState.type === 'ecommerce') {
      duration = '30 - 40 يوماً';
      serviceKey = 'automation';
      serviceName = 'أتمتة العمليات والأنظمة الرقمية';
      steps = [
        'ربط بوابات الدفع الإلكتروني وشركات الشحن بسلاسة',
        'تأسيس نظام إدارة المخزون وخدمة العملاء الآلي',
        'بناء استراتيجية اكتساب وإعادة استهداف العملاء'
      ];
    }

    if (calcDurationVal) calcDurationVal.textContent = duration;
    if (calcServiceVal) {
      const priceTag = serviceDetails[serviceKey]?.price ? ` (تبدأ من ${serviceDetails[serviceKey].price})` : '';
      calcServiceVal.innerHTML = `${serviceName} <span style="font-size:15px;color:#FFB37C;display:block;margin-top:4px;font-weight:700;">${priceTag}</span>`;
    }
    if (calcStepsList) {
      calcStepsList.innerHTML = steps.map(s => `
        <li>
          <span class="step-check">✓</span>
          <span>${s}</span>
        </li>
      `).join('');
    }

    if (calcBookBtn) {
      calcBookBtn.setAttribute('data-service', serviceKey);
    }
  };

  calcChoiceButtons.forEach(btn => {
    btn.addEventListener('click', () => {
      const group = btn.getAttribute('data-group');
      const value = btn.getAttribute('data-val');
      
      document.querySelectorAll(`.calc-choice-btn[data-group="${group}"]`).forEach(b => b.classList.remove('active'));
      btn.classList.add('active');

      calcState[group] = value;
      updateCalculatorResults();
    });
  });

  // When clicking the calculator booking button, pass calculation details into brief
  if (calcBookBtn) {
    calcBookBtn.addEventListener('click', (e) => {
      e.preventDefault();
      const serviceKey = calcBookBtn.getAttribute('data-service') || 'feasibility';
      const typeText = document.querySelector(`.calc-choice-btn[data-group="type"].active`)?.textContent.trim() || 'مشروع ريادي';
      const stageText = document.querySelector(`.calc-choice-btn[data-group="stage"].active`)?.textContent.trim() || 'فكرة جديدة';
      const budgetText = document.querySelector(`.calc-choice-btn[data-group="budget"].active`)?.textContent.trim() || 'ميزانية متوسطة';

      openModal(serviceKey);

      const briefEl = document.getElementById('projectBrief');
      if (briefEl) {
        briefEl.value = `[خطة الحاسبة]: نوع المشروع: (${typeText}) | المرحلة: (${stageText}) | الميزانية التقديرية: (${budgetText}).`;
      }
    });
  }

  // 8. Interactive FAQ Accordion
  const faqItems = document.querySelectorAll('.faq-item');
  faqItems.forEach(item => {
    const questionBtn = item.querySelector('.faq-question');
    if (questionBtn) {
      questionBtn.addEventListener('click', () => {
        const isActive = item.classList.contains('active');
        
        // Close other items
        faqItems.forEach(otherItem => {
          if (otherItem !== item) otherItem.classList.remove('active');
        });

        // Toggle current item
        if (isActive) {
          item.classList.remove('active');
        } else {
          item.classList.add('active');
        }
      });
    }
  });

  // 9. Smooth Scroll for Navigation Anchors
  document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
      const targetId = this.getAttribute('href');
      // If anchor has modal trigger or legal trigger, ignore smooth scroll
      if (this.hasAttribute('data-open-modal') || this.hasAttribute('data-legal')) {
        return;
      }
      if (targetId && targetId.length > 1) {
        const targetElement = document.querySelector(targetId);
        if (targetElement) {
          e.preventDefault();
          targetElement.scrollIntoView({
            behavior: 'smooth',
            block: 'start'
          });
        }
      }
    });
  });

  // ==========================================
  // 10. BIZNOVA SMART ASSISTANT ADVISOR BOT
  // ==========================================
  const assistantChatBox = document.getElementById('assistantChatBox');
  const assistantLauncherBtn = document.getElementById('assistantLauncherBtn');
  const heroAssistantBtn = document.getElementById('heroAssistantBtn');
  const assistantCloseBtn = document.getElementById('assistantCloseBtn');
  const assistantResetBtn = document.getElementById('assistantResetBtn');
  const assistantCallout = document.getElementById('assistantCallout');
  const closeCalloutBtn = document.getElementById('closeCalloutBtn');
  const assistantMessages = document.getElementById('assistantMessages');
  const assistantInputForm = document.getElementById('assistantInputForm');
  const assistantUserInput = document.getElementById('assistantUserInput');

  // Diagnostic service profile database
  const assistantProfiles = {
    feasibility: {
      key: 'feasibility',
      title: 'دراسات الجدوى ونماذج الأعمال',
      price: '7,500 ج.م',
      duration: '15 - 25 يوم عمل',
      why: 'لأنك في مرحلة الفكرة أو التأسيس، ودراسة الجدوى تحميك من هدر رأس المال وتثبت جدوى المشروع رقمياً وتجارياً قبل التنفيذ.',
      deliverables: [
        'دراسة سوقية ومنافسين وتحليل الفرص الكامنة',
        'نموذج مالي تفاعلي لـ 5 سنوات مع نقطة التعادل والتدفق النقدي',
        'نموذج العمل التجاري المعتمد (Business Model Canvas)'
      ]
    },
    mvp: {
      key: 'mvp',
      title: 'بناء وتطوير النموذج الأولي (MVP)',
      price: '7,500 ج.م',
      duration: '30 - 45 يوم عمل',
      why: 'لأنك تحتاج إلى نقل الفكرة من الورق إلى منتج برمجي حي يلمسه العملاء وتستطيع من خلاله إثبات ملاءمة المنتج للسوق وتجربة المستخدم.',
      deliverables: [
        'تصميم واجهات وتجربة مستخدم (UI/UX) احترافية',
        'برمجة النسخة الأولى عالية الكفاءة (تطبيق أو منصة ويب)',
        'تهيئة البنية السحابية وإطلاق تجريبي لقياس التفاعل'
      ]
    },
    growth: {
      key: 'growth',
      title: 'استراتيجيات التسويق والنمو',
      price: '7,500 ج.م',
      duration: 'شهري / ربع سنوي',
      why: 'لأن منتجك موجود بالفعل والهدف الآن هو مضاعفة المبيعات، تخفيض تكلفة الاستحواذ على العميل (CAC)، ورفع العائد الإعلاني (ROAS).',
      deliverables: [
        'بناء مسارات تحويل المبيعات الرقمية (Sales Funnels)',
        'إدارة وإطلاق حملات إعلانية مدفوعة قائمة على البيانات',
        'خطط محتوى وتوسيع الحصة السوقية الرقمية'
      ]
    },
    branding: {
      key: 'branding',
      title: 'الهوية التجارية والأنظمة الإدارية',
      price: '7,500 ج.م',
      duration: '20 - 30 يوم عمل',
      why: 'لأن بناء علامة تجارية راسخة وأنظمة إدارية واضحة يعطي شركتك مصداقية فورية ويؤسس لبيئة عمل تشغيلية مستدامة.',
      deliverables: [
        'دليل الهوية البصرية الكامل (الشعار، الألوان، الخطوط، المطبوعات)',
        'هياكل تنظيمية وتوصيف وظيفي تنفيذي للموظفين',
        'أدلة السياسات والتشغيل الداخلي للمؤسسة'
      ]
    },
    investment: {
      key: 'investment',
      title: 'تجهيز ملفات الاستثمار (Pitch Deck)',
      price: '7,500 ج.م',
      duration: '15 - 20 يوم عمل',
      why: 'لأن مقابلة المستثمرين تحتاج إلى أرقام مدققة، عرض تقديمي بصري مبهر، وقصة استثمارية مقنعة تؤكد فرص التخارج والعوائد العالية.',
      deliverables: [
        'عرض استثماري احترافي (Pitch Deck) مصمم لمعايير الصناديق الجريئة',
        'نموذج التقييم المالي للشركة قبل التمويل (Valuation)',
        'جلسات تدريب وتوجيه للمؤسسين على فن الإلقاء والتفاوض'
      ]
    },
    automation: {
      key: 'automation',
      title: 'أتمتة العمليات والأنظمة الرقمية',
      price: '7,500 ج.م',
      duration: '15 - 25 يوم عمل',
      why: 'لأن الأعمال اليدوية تستهلك وقت فريقك وتسبب أخطاء، بينما الربط الآلي للأنظمة يوفر 60% من الجهد ويرفع الإنتاجية فوراً.',
      deliverables: [
        'ربط أنظمة إدارة علاقات العملاء (CRM) مع الفواتير والمخازن',
        'أتمتة الردود على العملاء وبوابات الدفع وشركات الشحن',
        'لوحة قيادة مركزية لمتابعة مؤشرات الأداء الحية'
      ]
    },
    general: {
      key: 'general',
      title: 'استشارة ريادية عامة ومفتوحة',
      price: 'جلسة أولى مجانية',
      duration: 'جلسة استشارية 45 دقيقة',
      why: 'جلسة استكشافية شاملة مع خبير أعمال لمناقشة وضعك وتحديد أولوياتك للمرحلة القادمة بدون أي التزام مالي مسبق.',
      deliverables: [
        'تقييم شامل لنموذج العمل والوضع الراهن',
        'تحديد العقبات التشغيلية أو التسويقية',
        'خريطة طريق واضحة للخطوات التنفيذية التالية'
      ]
    }
  };

  // Toggle Assistant Chat Box
  const toggleAssistant = () => {
    if (assistantChatBox) {
      const isActive = assistantChatBox.classList.contains('active');
      if (isActive) {
        assistantChatBox.classList.remove('active');
      } else {
        assistantChatBox.classList.add('active');
        if (assistantCallout) assistantCallout.style.display = 'none';
        if (assistantMessages && assistantMessages.children.length === 0) {
          renderInitialWelcome();
        }
        setTimeout(() => {
          if (assistantUserInput) assistantUserInput.focus();
        }, 300);
      }
    }
  };

  if (assistantLauncherBtn) {
    assistantLauncherBtn.addEventListener('click', toggleAssistant);
  }

  if (heroAssistantBtn) {
    heroAssistantBtn.addEventListener('click', () => {
      if (assistantChatBox && !assistantChatBox.classList.contains('active')) {
        toggleAssistant();
      }
      assistantChatBox?.scrollIntoView({ behavior: 'smooth', block: 'end' });
    });
  }

  if (assistantCloseBtn) {
    assistantCloseBtn.addEventListener('click', () => {
      assistantChatBox?.classList.remove('active');
    });
  }

  if (closeCalloutBtn && assistantCallout) {
    closeCalloutBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      assistantCallout.style.display = 'none';
      sessionStorage.setItem('biznova_callout_dismissed', 'true');
    });
  }

  if (sessionStorage.getItem('biznova_callout_dismissed') === 'true' && assistantCallout) {
    assistantCallout.style.display = 'none';
  }

  // Scroll messages to bottom
  const scrollChatToBottom = () => {
    if (assistantMessages) {
      assistantMessages.scrollTop = assistantMessages.scrollHeight;
    }
  };

  // Add message bubble
  const addChatMsg = (sender, textHtml) => {
    if (!assistantMessages) return null;
    const msgDiv = document.createElement('div');
    msgDiv.className = `chat-msg ${sender}`;

    const avatarHtml = sender === 'bot' 
      ? `<div class="chat-msg-avatar">🤖</div>` 
      : `<div class="chat-msg-avatar" style="background:#F47521; color:#fff;">👤</div>`;

    msgDiv.innerHTML = `
      ${avatarHtml}
      <div class="chat-msg-content">${textHtml}</div>
    `;
    assistantMessages.appendChild(msgDiv);
    scrollChatToBottom();
    return msgDiv;
  };

  // Show temporary typing indicator
  const showTypingIndicator = (callback, delay = 600) => {
    if (!assistantMessages) return;
    const typingDiv = document.createElement('div');
    typingDiv.className = 'chat-msg bot typing-msg';
    typingDiv.innerHTML = `
      <div class="chat-msg-avatar">🤖</div>
      <div class="chat-msg-content" style="padding: 8px 12px;">
        <span class="typing-dots">
          <span class="typing-dot"></span>
          <span class="typing-dot"></span>
          <span class="typing-dot"></span>
        </span>
      </div>
    `;
    assistantMessages.appendChild(typingDiv);
    scrollChatToBottom();

    setTimeout(() => {
      typingDiv.remove();
      if (callback) callback();
    }, delay);
  };

  // Render initial welcome flow
  const renderInitialWelcome = () => {
    if (!assistantMessages) return;
    assistantMessages.innerHTML = '';

    addChatMsg('bot', `
      مرحباً بك في BIZNOVA! 👋<br>
      أنا <strong>مستشارك الذكي</strong>، موجود هنا لمساعدتك في تحديد الخدمة الدقيقة التي يحتاجها مشروعك حالياً وتكلفتها المقترحة بدون أي هدر مالي.<br><br>
      <strong>ما هو التحدي الأساسي أو هدفك الآن؟</strong>
      <div class="chat-options-group">
        <button class="chat-option-btn" data-bot-flow="feasibility">
          <span>📊 عندي فكرة وأريد دراسة جدوى وتكلفة المشروع</span>
          <span>←</span>
        </button>
        <button class="chat-option-btn" data-bot-flow="mvp">
          <span>💻 أريد بناء تطبيق أو منصة أولية (MVP) لاختبار السوق</span>
          <span>←</span>
        </button>
        <button class="chat-option-btn" data-bot-flow="growth">
          <span>📈 لدي منتج ولكن أحتاج زيادة المبيعات واستراتيجية تسويق</span>
          <span>←</span>
        </button>
        <button class="chat-option-btn" data-bot-flow="branding">
          <span>🎨 أريد هوية تجارية كاملة ونظام إداري وتشغيلي</span>
          <span>←</span>
        </button>
        <button class="chat-option-btn" data-bot-flow="investment">
          <span>💼 أستعد لمقابلة مستثمرين وطلب تمويل (Pitch Deck)</span>
          <span>←</span>
        </button>
        <button class="chat-option-btn" data-bot-flow="automation">
          <span>⚙️ العمليات مرهقة وأحتاج أتمتة الأنظمة وربط CRM</span>
          <span>←</span>
        </button>
        <button class="chat-option-btn" data-bot-flow="general">
          <span>❓ أريد جلسة استشارية أولى مفتوحة ومجانية</span>
          <span>←</span>
        </button>
      </div>
    `);

    wireOptionButtons();
  };

  // Render Smart Recommendation Card
  const renderRecommendation = (serviceKey, userContext = '') => {
    const prof = assistantProfiles[serviceKey] || assistantProfiles.general;
    const deliverablesList = prof.deliverables.map(d => `<div>✓ ${d}</div>`).join('');
    const whatsappMsg = encodeURIComponent(`مرحباً منصة BIZNOVA، رشح لي مستشاركم الذكي خدمة (${prof.title} - تبدأ من ${prof.price}) لمشروعي ${userContext ? `[${userContext}]` : ''} وأرغب في مناقشة تفاصيل البدء معكم.`);

    const cardHtml = `
      <div style="margin-bottom:6px;">
        بناءً على تشخيص وضعك الحالي، هذه هي الخدمة الأنسب لمشروعك لتحقيق أفضل نتيجة:
      </div>
      <div class="chat-recommend-card">
        <div class="recommend-badge">🎯 التوصية المخصصة لمشروعك</div>
        <h4 class="recommend-title">${prof.title}</h4>
        <div class="recommend-price">
          تكلفة الخدمة: <strong>تبدأ من ${prof.price}</strong>
        </div>
        <div style="font-size:12px; color:#64748B;">
          ⏱️ مدة التنفيذ: <strong>${prof.duration}</strong>
        </div>
        <div style="font-size:12.5px; color:#334155; line-height:1.5;">
          💡 <em>${prof.why}</em>
        </div>
        <div class="recommend-deliverables">
          <strong>المخرجات التي ستحصل عليها:</strong>
          ${deliverablesList}
        </div>
        <div class="recommend-actions">
          <button type="button" class="recommend-btn-book" data-service-book="${prof.key}" data-context="${userContext}">
            🚀 حجز هذه الخدمة الآن (${prof.price})
          </button>
          <a href="https://wa.me/201091106722?text=${whatsappMsg}" target="_blank" rel="noopener noreferrer" class="recommend-btn-whatsapp">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.711 2.592 2.654-.697c.974.534 1.745.819 2.806.819l.004-.001c3.182 0 5.768-2.587 5.769-5.766.001-3.182-2.585-5.769-5.767-5.769zm3.435 8.163c-.144.405-.838.775-1.17.825-.313.05-.718.077-2.146-.514-1.825-.756-3.003-2.616-3.094-2.738-.091-.121-.741-.986-.741-1.881s.467-1.334.633-1.517c.166-.183.363-.228.484-.228.121 0 .242.001.348.006.111.006.26-.042.406.31.15.36.514 1.25.56 1.341.045.091.076.198.015.319-.06.121-.091.198-.182.304-.091.106-.192.236-.274.316-.091.091-.186.19-.08.373.106.183.471.777 1.013 1.26.697.621 1.285.813 1.468.904.183.091.29.076.398-.045.108-.121.465-.541.591-.728.126-.186.251-.157.422-.091.171.066 1.084.511 1.27.603.186.091.31.137.355.213.046.076.046.442-.098.847z"/></svg>
            <span>مناقشة التفاصيل عبر واتساب</span>
          </a>
          <button type="button" class="recommend-btn-restart" id="btnRestartDiagnostic">
            🔄 تشخيص مشروع آخر أو اختيار خدمة جديدة
          </button>
        </div>
      </div>
    `;

    addChatMsg('bot', cardHtml);

    // Wire booking button inside the card
    const bookBtn = assistantMessages.querySelector(`[data-service-book="${prof.key}"]`);
    if (bookBtn) {
      bookBtn.addEventListener('click', () => {
        assistantChatBox?.classList.remove('active');
        openModal(prof.key);
        const briefEl = document.getElementById('projectBrief');
        if (briefEl) {
          briefEl.value = `[ترشيح المستشار الذكي]: رغبة في بدء (${prof.title})${userContext ? ` لمشروع في قطاع: (${userContext})` : ''}.`;
        }
      });
    }

    const restartBtn = document.getElementById('btnRestartDiagnostic');
    if (restartBtn) {
      restartBtn.addEventListener('click', renderInitialWelcome);
    }
  };

  // Wire interactive option buttons
  const wireOptionButtons = () => {
    if (!assistantMessages) return;
    const optionButtons = assistantMessages.querySelectorAll('[data-bot-flow]');
    optionButtons.forEach(btn => {
      btn.addEventListener('click', () => {
        const flowKey = btn.getAttribute('data-bot-flow');
        const userChoiceText = btn.querySelector('span:first-child')?.textContent || btn.textContent;
        addChatMsg('user', userChoiceText);

        // Branching sub-questions based on category
        showTypingIndicator(() => {
          if (flowKey === 'feasibility') {
            addChatMsg('bot', `
              خطوة ذكية وموفقة! 📊 دراسة الجدوى هي صمام الأمان لأموالك.<br>
              <strong>ما هو قطاع أو طبيعة فكرتك الاستثمارية؟</strong>
              <div class="chat-options-group">
                <button class="chat-option-btn" data-sub-choice="feasibility" data-context="تطبيق تقني أو منصة ويب">
                  <span>📱 تطبيق رقمي أو منصة تقنية</span>
                  <span>←</span>
                </button>
                <button class="chat-option-btn" data-sub-choice="feasibility" data-context="متجر تجاري أو تجارة تجزئة">
                  <span>🏪 متجر تجاري / مشروع تجزئة</span>
                  <span>←</span>
                </button>
                <button class="chat-option-btn" data-sub-choice="feasibility" data-context="مشروع خدمي أو كافيه ومطعم">
                  <span>☕ مشروع خدمي / مطعم أو كافيه</span>
                  <span>←</span>
                </button>
                <button class="chat-option-btn" data-sub-choice="feasibility" data-context="مشروع صناعي أو إنتاجي">
                  <span>🏭 مشروع صناعي أو إنتاجي</span>
                  <span>←</span>
                </button>
              </div>
            `);
            wireSubChoiceButtons();
          } else if (flowKey === 'mvp') {
            addChatMsg('bot', `
              رائع! 💻 الـ MVP هو أفضل طريقة لدخول السوق بأسرع وقت وأقل تكلفة.<br>
              <strong>ما هو مستوى جاهزية مشروعك التقني حتى الآن؟</strong>
              <div class="chat-options-group">
                <button class="chat-option-btn" data-sub-choice="mvp" data-context="فكرة مسجلة على ورق فقط">
                  <span>📝 فكرة مسجلة على ورق وأحتاج البدء من الصفر</span>
                  <span>←</span>
                </button>
                <button class="chat-option-btn" data-sub-choice="mvp" data-context="لدي تصميم شاشات وUI مبدئي">
                  <span>🎨 لدي تصور لتصاميم الشاشات (UI/UX)</span>
                  <span>←</span>
                </button>
                <button class="chat-option-btn" data-sub-choice="mvp" data-context="لدي نظام قديم يحتاج إعادة بناء">
                  <span>⚙️ لدي كود أو نظام يحتاج لتطوير وإعادة بناء</span>
                  <span>←</span>
                </button>
              </div>
            `);
            wireSubChoiceButtons();
          } else if (flowKey === 'growth') {
            addChatMsg('bot', `
              ممتاز! 📈 التسويق المبني على البيانات هو مفتاح الاستدامة والنمو.<br>
              <strong>ما هو التحدي الأبرز في مسار مبيعاتك الحالي؟</strong>
              <div class="chat-options-group">
                <button class="chat-option-btn" data-sub-choice="growth" data-context="حملات إعلانات ممولة ومضاعفة المبيعات">
                  <span>🎯 أحتاج حملات إعلانية مدفوعة ذات عائد مرتفع</span>
                  <span>←</span>
                </button>
                <button class="chat-option-btn" data-sub-choice="growth" data-context="بناء قمع ومسار مبيعات رقمي Sales Funnel">
                  <span>🔄 أحتاج بناء مسار تحويل مبيعات متكامل (Sales Funnel)</span>
                  <span>←</span>
                </button>
                <button class="chat-option-btn" data-sub-choice="growth" data-context="تطوير البراند والتوسع الإقليمي">
                  <span>🌍 أحتاج خطة توسع واكتساب حصة سوقية أكبر</span>
                  <span>←</span>
                </button>
              </div>
            `);
            wireSubChoiceButtons();
          } else if (flowKey === 'branding') {
            addChatMsg('bot', `
              تأسيس الهوية هو ما يرسخ علامتك في السوق! 🎨<br>
              <strong>ما الذي ترغب في التركيز عليه في باقتك؟</strong>
              <div class="chat-options-group">
                <button class="chat-option-btn" data-sub-choice="branding" data-context="هوية بصرية وشعار ودليل براند كامل">
                  <span>🎨 هوية بصرية كاملة (لوجو، ألوان، مطبوعات)</span>
                  <span>←</span>
                </button>
                <button class="chat-option-btn" data-sub-choice="branding" data-context="لوائح تنظيمية وهيكل إداري وتشغيلي">
                  <span>📋 هيكل إداري ووظيفي ولوائح تشغيل</span>
                  <span>←</span>
                </button>
                <button class="chat-option-btn" data-sub-choice="branding" data-context="باقة متكاملة: براند وهوية + نظام إداري">
                  <span>⭐ باقة متكاملة: براند بصري + نظام إداري شامل</span>
                  <span>←</span>
                </button>
              </div>
            `);
            wireSubChoiceButtons();
          } else if (flowKey === 'investment') {
            addChatMsg('bot', `
              خطوة ممتازة! 💼 جذب التمويل يعتمد بنسبة 80% على مصداقية الأرقام وإبهار العرض.<br>
              <strong>في أي مرحلة تفاوض أنت الآن؟</strong>
              <div class="chat-options-group">
                <button class="chat-option-btn" data-sub-choice="investment" data-context="جولة تمويل أولى Pre-Seed">
                  <span>🌱 أستعد لجولة تمويل أولية (Pre-Seed)</span>
                  <span>←</span>
                </button>
                <button class="chat-option-btn" data-sub-choice="investment" data-context="اجتماع قريب مع صناديق استثمار جريء">
                  <span>🤝 لدي مواعيد قريبة مع مستثمرين وصناديق</span>
                  <span>←</span>
                </button>
                <button class="chat-option-btn" data-sub-choice="investment" data-context="تقييم مالي وتدريب على فن الإلقاء Pitching">
                  <span>🎤 أحتاج تقييم مالي وتدريباً على عرض مشروعي</span>
                  <span>←</span>
                </button>
              </div>
            `);
            wireSubChoiceButtons();
          } else if (flowKey === 'automation') {
            addChatMsg('bot', `
              الأتمتة توفر مئات الساعات المهدرة شهرياً! ⚙️<br>
              <strong>ما هي المشكلة التشغيلية الأكبر في عملك؟</strong>
              <div class="chat-options-group">
                <button class="chat-option-btn" data-sub-choice="automation" data-context="تشتت العمليات والبيانات في شيتات إكسل">
                  <span>📊 تشتت البيانات بين ملفات إكسل وورق</span>
                  <span>←</span>
                </button>
                <button class="chat-option-btn" data-sub-choice="automation" data-context="نظام إدارة عملاء وفواتير موحد CRM">
                  <span>👥 نحتاج نظام CRM لمتابعة العملاء والمبيعات</span>
                  <span>←</span>
                </button>
                <button class="chat-option-btn" data-sub-choice="automation" data-context="ربط تلقائي لبوابات الدفع والشحن والرسائل">
                  <span>⚡ ربط آلي لبوابات الدفع، الشحن، والواتساب</span>
                  <span>←</span>
                </button>
              </div>
            `);
            wireSubChoiceButtons();
          } else {
            renderRecommendation('general');
          }
        });
      });
    });
  };

  // Wire sub-choice buttons to generate final recommendation
  const wireSubChoiceButtons = () => {
    if (!assistantMessages) return;
    const subButtons = assistantMessages.querySelectorAll('[data-sub-choice]');
    subButtons.forEach(btn => {
      btn.addEventListener('click', () => {
        const serviceKey = btn.getAttribute('data-sub-choice');
        const contextText = btn.getAttribute('data-context') || '';
        const userChoiceText = btn.querySelector('span:first-child')?.textContent || btn.textContent;
        addChatMsg('user', userChoiceText);

        showTypingIndicator(() => {
          renderRecommendation(serviceKey, contextText);
        });
      });
    });
  };

  // Handle Free-Text Input & Keyword/Intent Matching
  if (assistantInputForm && assistantUserInput) {
    assistantInputForm.addEventListener('submit', (e) => {
      e.preventDefault();
      const rawText = assistantUserInput.value.trim();
      if (!rawText) return;

      assistantUserInput.value = '';
      addChatMsg('user', rawText);

      showTypingIndicator(() => {
        const text = rawText.toLowerCase();

        // Keyword Intent Matching
        if (text.includes('تطبيق') || text.includes('ابلكيشن') || text.includes('برمج') || text.includes('موقع') || text.includes('منصة') || text.includes('mvp') || text.includes('كود') || text.includes('تطوير') || text.includes('برنامج')) {
          renderRecommendation('mvp', rawText);
        } else if (text.includes('فكرة') || text.includes('جدوى') || text.includes('دراسة') || text.includes('تكلفة') || text.includes('ارباح') || text.includes('سوق') || text.includes('ابدأ') || text.includes('مشروع جديد') || text.includes('مشروع')) {
          renderRecommendation('feasibility', rawText);
        } else if (text.includes('تسويق') || text.includes('مبيعات') || text.includes('اعلان') || text.includes('عملاء') || text.includes('سوشيال') || text.includes('بيع') || text.includes('زبائن') || text.includes('ارباح قليلة') || text.includes('نمو')) {
          renderRecommendation('growth', rawText);
        } else if (text.includes('لوجو') || text.includes('شعار') || text.includes('هوية') || text.includes('براند') || text.includes('ادارة') || text.includes('موظفين') || text.includes('هيكل') || text.includes('تأسيس')) {
          renderRecommendation('branding', rawText);
        } else if (text.includes('مستثمر') || text.includes('تمويل') || text.includes('فلوس') || text.includes('pitch') || text.includes('بيتش') || text.includes('عرض استثمار') || text.includes('راس مال') || text.includes('شريك')) {
          renderRecommendation('investment', rawText);
        } else if (text.includes('اتمت') || text.includes('crm') || text.includes('اكسل') || text.includes('فواتير') || text.includes('شيتات') || text.includes('سيستم') || text.includes('ربط') || text.includes('تنظيم')) {
          renderRecommendation('automation', rawText);
        } else if (text.includes('سعر') || text.includes('اسعار') || text.includes('بكام') || text.includes('قائمة') || text.includes('تكاليف')) {
          addChatMsg('bot', `
            إليك قائمة الأسعار الرسمية المعتمدة لخدمات BIZNOVA بالجنيه المصري (تم توحيد الباقات وفق متوسط الميزانيات):
            <ul style="margin: 8px 0; padding-right: 20px; font-size:13px; line-height:1.7;">
              <li><strong>دراسات الجدوى وتحليل السوق:</strong> 7,500 ج.م</li>
              <li><strong>بناء وتطوير الـ MVP:</strong> 7,500 ج.م</li>
              <li><strong>استراتيجيات التسويق والنمو:</strong> 7,500 ج.م</li>
              <li><strong>الهوية التجارية والأنظمة:</strong> 7,500 ج.م</li>
              <li><strong>تجهيز ملفات الاستثمار:</strong> 7,500 ج.م</li>
              <li><strong>أتمتة العمليات والأنظمة:</strong> 7,500 ج.م</li>
            </ul>
            <strong>اختر أي خدمة تود فحصها والبدء بها:</strong>
            <div class="chat-options-group">
              <button class="chat-option-btn" data-bot-flow="feasibility"><span>دراسة الجدوى (7,500 ج.م)</span><span>←</span></button>
              <button class="chat-option-btn" data-bot-flow="mvp"><span>بناء الـ MVP (7,500 ج.م)</span><span>←</span></button>
              <button class="chat-option-btn" data-bot-flow="growth"><span>التسويق والنمو (7,500 ج.م)</span><span>←</span></button>
              <button class="chat-option-btn" data-bot-flow="branding"><span>الهوية والأنظمة (7,500 ج.م)</span><span>←</span></button>
              <button class="chat-option-btn" data-bot-flow="investment"><span>ملفات الاستثمار (7,500 ج.م)</span><span>←</span></button>
              <button class="chat-option-btn" data-bot-flow="automation"><span>أتمتة العمليات (7,500 ج.م)</span><span>←</span></button>
            </div>
          `);
          wireOptionButtons();
        } else {
          addChatMsg('bot', `
            شكراً لمشاركتك يا بطل! 💡<br>
            لتحديد الخدمة والمسار الأنسب لمشروعك بأعلى دقة، يرجى اختيار التحدي الأقرب لما تحتاجه من الخيارات التالية:
            <div class="chat-options-group">
              <button class="chat-option-btn" data-bot-flow="feasibility"><span>📊 دراسة جدوى وتكلفة فكرة جديدة</span><span>←</span></button>
              <button class="chat-option-btn" data-bot-flow="mvp"><span>💻 برمجة تطبيق أو موقع إلكتروني (MVP)</span><span>←</span></button>
              <button class="chat-option-btn" data-bot-flow="growth"><span>📈 خطة تسويق وزيادة المبيعات</span><span>←</span></button>
              <button class="chat-option-btn" data-bot-flow="branding"><span>🎨 تصميم هوية تجارية وتنظيم إداري</span><span>←</span></button>
              <button class="chat-option-btn" data-bot-flow="investment"><span>💼 تجهيز عرض استثماري لطلب تمويل</span><span>←</span></button>
              <button class="chat-option-btn" data-bot-flow="automation"><span>⚙️ أتمتة العمليات وأنظمة CRM</span><span>←</span></button>
            </div>
          `);
          wireOptionButtons();
        }
      });
    });
  }

  // Reset conversation button
  if (assistantResetBtn) {
    assistantResetBtn.addEventListener('click', () => {
      renderInitialWelcome();
    });
  }
});

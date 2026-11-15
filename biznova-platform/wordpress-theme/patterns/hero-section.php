<?php
/**
 * Title: BIZNOVA Hero Section
 * Slug: biznova/hero-section
 * Categories: biznova-hero
 * Description: The primary high-converting hero section with headline, two CTAs and entrepreneurial dashboard mockup.
 */
if (!defined('ABSPATH')) {
    exit; // Prevent direct access
}
?>
<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"80px","bottom":"100px"}},"color":{"background":"#FFFFFF"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull has-background" style="background-color:#FFFFFF;padding-top:80px;padding-bottom:100px">
  <!-- wp:columns {"align":"wide","style":{"spacing":{"blockGap":"48px"}}} -->
  <div class="wp-block-columns alignwide">
    
    <!-- wp:column {"verticalAlignment":"center","width":"58%"} -->
    <div class="wp-block-column is-vertically-aligned-center" style="flex-basis:58%">
      
      <!-- wp:paragraph {"style":{"typography":{"fontSize":"14px","fontWeight":"700"}},"backgroundColor":"soft-offwhite","textColor":"secondary-orange","className":"badge-pill"} -->
      <p class="has-secondary-orange-color has-soft-offwhite-background-color has-text-color has-background badge-pill" style="font-size:14px;font-weight:700">🚀 منصتك الرائدة في هندسة المشاريع والنمو المستدام</p>
      <!-- /wp:paragraph -->

      <!-- wp:heading {"level":1,"style":{"typography":{"fontSize":"46px","lineHeight":"1.25"}},"textColor":"primary-navy"} -->
      <h1 class="wp-block-heading has-primary-navy-color has-text-color" style="font-size:46px;line-height:1.25">
        من فكرة على ورق.. <br>إلى <span style="color:#F47521;">شركة رائدة في السوق!</span>
      </h1>
      <!-- /wp:heading -->

      <!-- wp:paragraph {"style":{"typography":{"fontSize":"19px","lineHeight":"1.8"}},"textColor":"text-muted"} -->
      <p class="has-text-muted-color has-text-color" style="font-size:19px;line-height:1.8">
        نساعدك في <strong>BIZNOVA</strong> على تحويل أفكارك الطموحة إلى مشاريع قائمة وناجحة بخطوات علمية ومدروسة، مدعومة باستشارات متخصصة، نماذج عمل قابلة للتوسع، وتطبيق تنفيذي حاسم.
      </p>
      <!-- /wp:paragraph -->

      <!-- wp:buttons {"style":{"spacing":{"blockGap":"16px","margin":{"top":"32px","bottom":"40px"}}}} -->
      <div class="wp-block-buttons" style="margin-top:32px;margin-bottom:40px">
        <!-- wp:button {"backgroundColor":"secondary-orange","style":{"border":{"radius":"12px"},"typography":{"fontWeight":"700"}}} -->
        <div class="wp-block-button"><a class="wp-block-button__link has-secondary-orange-background-color has-background wp-element-button" style="border-radius:12px;font-weight:700" href="#contact">ابدأ رحلتك الآن ←</a></div>
        <!-- /wp:button -->

        <!-- wp:button {"className":"is-style-outline-navy","style":{"border":{"radius":"12px"},"typography":{"fontWeight":"700"}}} -->
        <div class="wp-block-button is-style-outline-navy"><a class="wp-block-button__link wp-element-button" style="border-radius:12px;font-weight:700" href="#contact">استشر خبيراً</a></div>
        <!-- /wp:button -->
      </div>
      <!-- /wp:buttons -->

      <!-- wp:columns {"style":{"spacing":{"padding":{"top":"24px"}},"border":{"top":{"color":"#E2E8F0","width":"1px"}}}} -->
      <div class="wp-block-columns" style="border-top-color:#E2E8F0;border-top-width:1px;padding-top:24px">
        <!-- wp:column -->
        <div class="wp-block-column">
          <!-- wp:paragraph {"style":{"typography":{"fontSize":"24px","fontWeight":"800"}},"textColor":"primary-navy"} -->
          <p class="has-primary-navy-color has-text-color" style="font-size:24px;font-weight:800;margin:0"><span id="statConsultations">0</span> <span style="color:#F47521;font-size:14px;">استشارة</span></p>
          <!-- /wp:paragraph -->
          <!-- wp:paragraph {"style":{"typography":{"fontSize":"13px"}},"textColor":"text-muted"} -->
          <p class="has-text-muted-color has-text-color" style="font-size:13px;margin:0">جلسات استشارية تم حجزها</p>
          <!-- /wp:paragraph -->
        </div>
        <!-- /wp:column -->

        <!-- wp:column -->
        <div class="wp-block-column">
          <!-- wp:paragraph {"style":{"typography":{"fontSize":"24px","fontWeight":"800"}},"textColor":"primary-navy"} -->
          <p class="has-primary-navy-color has-text-color" style="font-size:24px;font-weight:800;margin:0"><span id="statVisitors">0</span> <span style="color:#F47521;font-size:14px;">زيارة</span></p>
          <!-- /wp:paragraph -->
          <!-- wp:paragraph {"style":{"typography":{"fontSize":"13px"}},"textColor":"text-muted"} -->
          <p class="has-text-muted-color has-text-color" style="font-size:13px;margin:0">زيارات حقيقية للمنصة</p>
          <!-- /wp:paragraph -->
        </div>
        <!-- /wp:column -->

        <!-- wp:column -->
        <div class="wp-block-column">
          <!-- wp:paragraph {"style":{"typography":{"fontSize":"24px","fontWeight":"800"}},"textColor":"primary-navy"} -->
          <p class="has-primary-navy-color has-text-color" style="font-size:24px;font-weight:800;margin:0"><span id="statActiveProjects">0</span> <span style="color:#F47521;font-size:14px;">مشروع</span></p>
          <!-- /wp:paragraph -->
          <!-- wp:paragraph {"style":{"typography":{"fontSize":"13px"}},"textColor":"text-muted"} -->
          <p class="has-text-muted-color has-text-color" style="font-size:13px;margin:0">مشاريع تم إنجازها وتسليمها بنجاح</p>
          <!-- /wp:paragraph -->
        </div>
        <!-- /wp:column -->
      </div>
      <!-- /wp:columns -->

    </div>
    <!-- /wp:column -->

    <!-- wp:column {"verticalAlignment":"center","width":"42%"} -->
    <div class="wp-block-column is-vertically-aligned-center" style="flex-basis:42%">
      <!-- wp:group {"style":{"spacing":{"padding":{"top":"28px","bottom":"28px","left":"28px","right":"28px"}},"border":{"radius":"24px","width":"1px","color":"#E2E8F0"}},"shadow":"card-soft","backgroundColor":"pure-white"} -->
      <div class="wp-block-group has-pure-white-background-color has-background" style="border-color:#E2E8F0;border-width:1px;border-radius:24px;padding:28px">
        
        <!-- wp:paragraph {"style":{"typography":{"fontWeight":"700"}},"textColor":"primary-navy"} -->
        <p class="has-primary-navy-color has-text-color" style="font-weight:700">🟢 لوحة مؤشرات BIZNOVA للنمو والريادة</p>
        <!-- /wp:paragraph -->

        <!-- wp:columns {"style":{"spacing":{"blockGap":"16px"}}} -->
        <div class="wp-block-columns">
          <!-- wp:column -->
          <div class="wp-block-column" style="background:#F8FAFC;padding:16px;border-radius:12px;border:1px solid #EDF2F7">
            <p style="font-size:12px;color:#526484;margin:0">معدل تسارع الإيرادات</p>
            <p style="font-size:22px;font-weight:800;color:#0C1E3C;margin:4px 0">+340%</p>
            <p style="font-size:11px;font-weight:700;color:#10B981;margin:0">▲ نمو فصلي متسارع</p>
          </div>
          <!-- /wp:column -->

          <!-- wp:column -->
          <div class="wp-block-column" style="background:#F8FAFC;padding:16px;border-radius:12px;border:1px solid #EDF2F7">
            <p style="font-size:12px;color:#526484;margin:0">جاهزية الـ MVP للسوق</p>
            <p style="font-size:22px;font-weight:800;color:#0C1E3C;margin:4px 0">96.8%</p>
            <p style="font-size:11px;font-weight:700;color:#F47521;margin:0">✓ إطلاق النسخة الأولى</p>
          </div>
          <!-- /wp:column -->
        </div>
        <!-- /wp:columns -->

        <!-- wp:group {"style":{"spacing":{"padding":{"top":"16px","bottom":"16px","left":"16px","right":"16px"},"margin":{"top":"16px"}},"border":{"radius":"12px"}},"backgroundColor":"soft-offwhite"} -->
        <div class="wp-block-group has-soft-offwhite-background-color has-background" style="border-radius:12px;margin-top:16px;padding:16px">
          <p style="font-size:13px;font-weight:700;color:#0C1E3C;margin-bottom:8px">مسار تحويل الفكرة إلى قيمة سوقية</p>
          <div style="height:8px;background:#E2E8F0;border-radius:99px;overflow:hidden">
            <div style="width:78%;height:100%;background:linear-gradient(90deg, #0C1E3C 0%, #F47521 100%)"></div>
          </div>
          <div style="display:flex;justify-content:space-between;font-size:11px;color:#526484;margin-top:6px">
            <span>دراسة الجدوى</span>
            <span>بناء MVP</span>
            <span>الإطلاق</span>
            <span style="color:#F47521;font-weight:700">التوسع 🚀</span>
          </div>
        </div>
        <!-- /wp:group -->

      </div>
      <!-- /wp:group -->
    </div>
    <!-- /wp:column -->

  </div>
  <!-- /wp:columns -->
</div>
<!-- /wp:group -->

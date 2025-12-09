<?php
/**
 * SPA Login Popup
 * 
 * @package Samuel Piasecký ACADEMY
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Len pre frontend a neprihlásených
if (is_admin()) {
    return;
}

add_action('wp_footer', 'spa_login_popup_html', 100);

function spa_login_popup_html() {
    
    if (is_user_logged_in()) {
        return;
    }
    
    if (is_page(array('login', 'prihlasenie', 'lost-password', 'nove-heslo'))) {
        return;
    }
    
    ?>
    <div id="spa-login-popup-overlay" class="spa-popup-overlay">
        <div class="spa-popup-container">
            <button type="button" class="spa-popup-close">&times;</button>
            <div class="spa-popup-content">
                <?php echo do_shortcode('[spa_login]'); ?>
            </div>
        </div>
    </div>
    
    <style>
    .spa-popup-overlay{display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.6);backdrop-filter:blur(4px);z-index:999999;opacity:0;transition:opacity 0.3s;overflow-y:auto;padding:20px;box-sizing:border-box}
    .spa-popup-overlay.active{display:flex;align-items:center;justify-content:center;opacity:1}
    .spa-popup-container{position:relative;width:100%;max-width:480px;max-height:90vh;overflow-y:auto;animation:spaPopIn 0.3s ease}
    @keyframes spaPopIn{from{opacity:0;transform:translateY(-30px)}to{opacity:1;transform:translateY(0)}}
    .spa-popup-close{position:absolute;top:12px;right:10px;width:35px;height:35px;border:none;background:#fff;border-radius:50%;font-size:28px;line-height:1;color:#666;cursor:pointer;z-index:10;box-shadow:0 2px 10px rgba(0,0,0,0.1)}
    .spa-popup-close:hover{background:#E4002B;color:#fff}
    .spa-popup-content .spa-login-wrapper{margin:0;max-width:100%}
    </style>
    
    <script>
    (function(){
        var o=document.getElementById('spa-login-popup-overlay');
        if(!o)return;
        
        o.querySelector('.spa-popup-close').onclick=function(){o.classList.remove('active');document.body.style.overflow='';};
        o.onclick=function(e){if(e.target===o){o.classList.remove('active');document.body.style.overflow='';}};
        document.onkeydown=function(e){if(e.key==='Escape'&&o.classList.contains('active')){o.classList.remove('active');document.body.style.overflow='';}};
        
        function hook(){
            document.querySelectorAll('.ct-header-account, [data-id="account"] a').forEach(function(a){
                if(a.href&&(a.href.indexOf('logout')>-1||a.href.indexOf('dashboard')>-1))return;
                a.onclick=function(e){e.preventDefault();o.classList.add('active');document.body.style.overflow='hidden';};
            });
        }
        
        document.addEventListener('DOMContentLoaded',hook);
        new MutationObserver(hook).observe(document.body,{childList:true,subtree:true});
    })();
    </script>
    <?php
}
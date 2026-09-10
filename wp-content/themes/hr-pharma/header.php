<!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>" />

<link rel="preconnect" href="https://fonts.gstatic.com">
<?php wp_head(); ?>

<!-- Google Tag Manager --> 
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start': new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0], j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src= 'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f); })(window,document,'script','dataLayer','GTM-PTRXBTL');</script>
<!-- End Google Tag Manager -->
</head>

<body <?php body_class(is_contract_manufacturing() ? 'cm-template' : ''); ?>>
    <!-- Google Tag Manager (noscript) -->
    <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM- PTRXBTL"
    height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript> 
    <!-- End Google Tag Manager (noscript) -->

    <?php get_search_form( $echo = true ); ?>

    <div class="wrapper">  
        <header class="header">
            <div class="header__top grid flex">
                <div class="header__top-left flex">
                    <h1 class="logo">
                        <a href="<?php echo esc_url( home_url( '/' ) ); ?>" title="<?php echo esc_attr( get_bloginfo( 'name', 'display' ) ); ?>" rel="home"><?php bloginfo( 'name' ); ?></a>
                    </h1>
                    <h2 class="slogan">We are HR</h2>
                </div>
                <div class="header__top-right">
                    <nav class="nav">
                        <?php 
                            if (is_contract_manufacturing()) {
                                wp_nav_menu( array( 'theme_location' => 'contract_manufacturing_top' ) ); 
                            } else {
                                wp_nav_menu( array( 'theme_location' => 'top' ) ); 
                            }
                        ?>
                    </nav>
                </div>
            </div>

            <?php is_contract_manufacturing(); ?>


            <div class="header__bottom">
                <div class="grid flex">    
                    <nav class="nav dropdown">                        
                        <?php 
                            if (is_contract_manufacturing()) {
                                wp_nav_menu( array( 'theme_location' => 'contract_manufacturing_primary' ) ); 
                            } else {
                                wp_nav_menu( array( 'theme_location' => 'primary' ) ); 
                            }
                        ?>
                    </nav>
                    <a class="search-display" href="#"><i class="fas fa-search"></i></a>
                </div>
            </div>
        </header>

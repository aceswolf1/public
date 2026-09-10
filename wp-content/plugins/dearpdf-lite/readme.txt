=== PDF Viewer & 3D PDF Flipbook - DearPDF ===
Plugin Name: PDF Viewer & 3D PDF Flipbook - DearPDF
Author: dearhive
Author URI: https://dearpdf.com/
Contributors: deip, dearhive
Tags: pdf viewer, pdf flipbook, pdf embed, flipbook,flip book, 3d flipbook
Requires at least: 3.0.1
Tested up to: 6.0
Stable tag: 1.2.62
Requires PHP: 5.2.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Embed PDF Viewer and 3D PDF Flipbook in your WordPress post and pages. Just add the link, and you have your PDF viewer ready.

== Description ==

DearPDF is a simple yet powerful WordPress PDF Flipbook and PDF Viewer plugin to display your PDFs. Embed your pdfs and display them similar to desktop PDF viewers or interactive PDF flipbooks - as per your choice.<br>

[DearPDF Demos and Test Page](https://dearpdf.com/)

✔ <strong>Your one-stop PDF Viewing solution! No more confusion between PDF viewer and PDF flipbook plugins</strong>
✔ <strong>Easy and versatile PDF viewer to display irregular pages and text oriented documents</strong>
✔ <strong>Realistic 3D PDF flipbooks to showcase your amazing PDF documents, menus, annual reports, design portfolio</strong>
✔ <strong>Based on our best rated <a href="https://wordpress.org/plugins/3d-flipbook-dflip-lite/">WordPress flipbook plugin</a> on WordPress and Codecanyon(former)</strong>


<h3> DearPDF variations </h3>
<ul>
<li><strong>Vertical Viewer</strong> - The traditional method of viewing/reading PDFs is also available. This is the best suitable when the PDF document has different page sizes. With this all sizes of PDF are covered by DearPDF.<a href="https://dearpdf.com/docs/embed-vertical-pdf-reader/">PDF Viewer Demo</a></li>
<li><strong>3D PDF Flipbook</strong> - Realistic look for your lively PDF. DearPDF takes your flat PDFs and converts them to interactive and real life books with the help of cutting edge WegGL technology. All high-tech complex work happens behind the scenes, while your customers enjoy your PDF content with delight. 3D Flipbook is our pride and soon will be yours. <a href="https://dearpdf.com/docs/embed-3d-flipbook/">3D Flipbook Demo</a></li>
<li><strong>2D PDF Flipbook</strong> - When 3D flipbook is not supported by devices or your taste, you are still covered! 2D flipbook use CSS and HTMl5 tags to create flippable and interactive flipbook. The famous variant before 3D flipbook is still a worthy alternative.<a href="https://dearpdf.com/docs/embed-2d-flipbook/">2D Flipbook Demo</a></li>
</ul>


### Just 5 Steps! ###
WordPress is famous for its easy to learn and use structure. DearPDF follows WordPress post structure for creating and managing PDF viewers. It’s easy and simple just like creating a post.

1. Install DearPDF
2. Create a DearPDF Post
3. Provide a link to your PDF and save the post
4. Copy the shortcode and paste to your page
5. Publish and PDF viewer is ready to launch on your page!

### Features ###
 - Vertical PDF viewer
 - Flipbook modes
 - Easy WordPress Post structure
 - [Embedded mode](https://dearpdf.com/docs/embed-vertical-pdf-reader/):  is the default structure to display PDFs
 - Basic Button Popup: Display your PDFs on demand, best for multiple PDFs in a single page
 - Table of contents for PDF
 - Page thumbnails preview
 - Deep linking to pages of a PDF via share button
 - PDF download enable and disable
 - Supports any number of pages, more than 1000 pages and 500 MB.
 - Partial loading (on-demand pages) make using 500 MB files load and start with just 5 MB of data

### Pro Features ###
 - More Popup Variations like thumbs and links
 - Auto PDF Thumbnails
 - PDF Links
 - Global Settings
 - 3D Book Cover for realistic Book structure
 - Customizable Flipbook Page modes - You can set flipbook to single or double page
 - PDF Partial loading size option

<a href="https://dearpdf.com/docs/lite-vs-pro-comparison/">Lite vs Pro Full Comparision</a>

== Installation ==

Thanks for using DearPDF, on your WordPress site. This page is intended for lite users that are using the lite edition and checking the features of DearPDF WordPress plugin. Please go through the FAQs that occur during using the lite version.

If these installation FAQs doesn’t solve your query and issues, please contact using the official WordPress support page.

= How to install DearPDF WordPress plugin? =

1. Goto **'Plugins > Add New'** inside WordPress admin
2. Search for **'dearpdf'** in the search plugins text box
3. Locate **DearPDF** and click on **'Install Now'** button
4. Click on **'Activate'** to activate the plugin


== Frequently Asked Questions ==

= How to install DearPDF in my WordPress site? =
Follow the <a href="https://dearpdf.com/docs/how-to-install-and-activate-dearpdf-lite/">Installation Guide</a>.


= How many pages can PDF viewer support and how big the PDF file can be? =

DearPDF uses PDF.js to render the PDF pages. At any given time it requires only 6 pages of data at max. So the amount of pages in a PDF file doesn't really affect the viewer.
However, the vertical PDF viewer loads all pages, so it is not as efficient as flipbooks.
If partial loading is available in your server, it only loads 6 Pages of data to open the PDF and this makes it resource friendly and traffic friendly. Other PDF viewers might crash with huge files, DearPDF will last longer.


= Creating a PDF Viewer WordPress Post with PDF =
1. Goto **'DearPDF > Add Post'**
2. Enter the title of the PDF Viewer in Title box
3. Click on **'Select PDF'** button and select a PDF. Upload the PDF file if you have not uploaded already and select the PDF file.
4. Under Layout Tab, set Viewer Type to Vertical Reader
4. Click on **'Publish'** button and the page should save and reload

= Adding PDF viewer to a WordPress Page =

1. From the DearPDF post you just created, copy the shortcode from **'Shortcode'** box. Similar to [dearpdf id="123"][/dearpdf]
2. Goto the page where you want to insert the viewer and paste the shortcode


= Start or open PDF viewer at certain page? =

You can set the opening page of PDF viewer with shortcode:
**[dearpdf id="123" data-page="5"]**
This will create your PDF viewer to open at page 5 when it starts.


= How to set custom text to flipbook button? =

You can set the custom text of flipbook button with shortcode:
**[dearpdf id="123"]My Custom Text for Button[/dearpdf]**
This will create a Button with the "My Custom Text for Button"


== Screenshots ==

1. PDF Reader
2. 3D PDF Flipbook
2. 2D PDF Flipbook

== Changelog ==

This lite version is available in WordPress plugin directory since version 1.0.76

 = 1.2.62: =
 * Fix: Rotated pages support

 = 1.2.61: =
 * Add: WordPress version 6.0 compatiility
 * Fix: Double Internal Format support for single page PDF
 * Fix: Zoom issues in flipbook booklet mode solved
 * Improvement: Pagenumber displayed in thumbs
 * Improvement: Partial loading message display
 * Improvement: `dearPdfLocation` is now converted to absolute if relative path is provided
 * Improvement: Fullscreen improved
 * Improvement: TextureSize is maxLimited to 3200, reduces crashes in highResDevices

 = 1.1.84: =
 * Fix: data-page option was missing
 * Fix: Ajax shortcode issue patch
 * Improvement: Resize logic improvement
 * Improvement: Page rendering cleanup

 = 1.1.75: =
 * Added: Sound option is added
 * Added: Documentation links for settings
 * Fix: Security fixes related to XSS
 * Fix: Vertical reader fullscreen and resize doesn't change page position
 * Fix: Wp-admin bar and pdf viewer height conflict
 * Improvement: Flipbook 3D flipbook shadows are smoother
 * Improvement: Flipbook Single page mode used in mobile
 * Improvement: Vertical reader is optimized, has new scroll
 * Improvement: Settings categorized, shortcodes organized

 = 1.0.116: =
 * Fix: Fullscreen issues
 * Fix: Auto HTML links to pdf viewer is now refined
 * Improvement: Supports pdf viewer inside tabs

 = 1.0.92: =

 * Added: Read Direction option. Now supports RTL PDFs
 * Added: Disable Range option. Useful when servers don't support proper partial loading - happens with improper nginx servers.

 = 1.0.89: =

 * Added: Height option
 * Added: Background Color option
 * Added: Background Image option
 * Added: Download Enable/Disable option
 * Fixed: Mobile single page mode and touch in PDF Flipbook Viewer
 * Improvement: Default Page size calculation in double internal PDF Flipbook
 * Improvement: PDFPage cleanup and Double internal issue
 * Improvement: Vertical PDF Reader Optimized and issue cleanup

 = 1.0.76: =

 * Plugin released on WordPress.org

== Upgrade Notice ==


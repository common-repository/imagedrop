(function ($) {

    // The <select> element
    var $id_s = null;

    var currentSlide = 0,
        noSlides = -1,
        noImages = -1,
        gridSize = -1,
        new_image_poll_id = null; // setTimeout id for the new_image_poll() function


    // This function will try to resize the image to the desirable size
    // after dropping it into the editor
    //
    function resize_id() {

        if (typeof(tinymce) !== "undefined" && tinymce.editors.length > 0) {
            $(tinymce.editors[0].getBody()).find('.imagedrop').each(function () {
                var $this = $(this); // $this references the image

                var c, w, h, u;

                // CSS classes
                c = [ 'alignnone', 'wp-image-' + $this.data('id') ];

                switch ($id_s.val()) {

                    case "thumb":
                        w = $this.data('thumbnail-width');
                        h = $this.data('thumbnail-height');
                        u = $this.data('thumbnail-url');
                        c.push('size-thumbnail');
                        break;

                    case "medium":
                        w = $this.data('medium-width');
                        h = $this.data('medium-height');
                        u = $this.data('medium-url');
                        c.push('size-medium');
                        break;

                    case "large":
                        w = $this.data("large-width");
                        h = $this.data("large-height");
                        u = $this.data("large-url");
                        c.push('size-large');
                        break;

                    default:
                        w = $this.data('width');
                        h = $this.data('height');
                        u = $this.data('url');
                        c.push('size-full');
                        break;
                }

                var $link = $("<a></a>").attr('href', $this.data('url'));

                $this.attr('width', w).
                    attr('height', h).
                    attr('src', u).
                    removeClass('imagedrop').
                    addClass(c.join(" ")).
                    wrap($link);

                $this.attr({'data-id': null,
                    'data-width': null,
                    'data-height': null,
                    'data-url': null,
                    'data-large-url': null,
                    'data-large-width': null,
                    'data-large-height': null,
                    'data-medium-url': null,
                    'data-medium-width': null,
                    'data-medium-height': null,
                    'data-thumbnail-url': null,
                    'data-thumbnail-width': null,
                    'data-thumbnail-height': null});
            });
        }

        setTimeout(resize_id, 100);
    }

    // This function will use ajax to poll the DB for how many images
    // it's currently storing. If the number of images has changed the
    // slides of images needs to be refreshed.
    //
    // Note: this polling will be stopped when performing a search
    //
    function new_image_poll() {
        var data = {action: 'id_image_count'};

        jQuery.post(ajaxurl, data, function (response) {

            // Only accept numeric responses
            if (!response.match(/[0-9]+/)) {
                return;
            }

            // The number of images differs
            if (response !== noImages) {
                refresh();
            }

            new_image_poll_id = setTimeout(new_image_poll, 30000);

        });
    }

    // This function will refresh the meta_box by reloading it completely
    // from the server
    //
    function refresh() {

        var data = {action: 'id_load_meta_box'};
        jQuery.post(ajaxurl, data, function (response) {

            // Update HTML
            $("#imagedrop .inside").html(response);

            // id_data should be included in the response

            // Reset all variables
            noSlides = id_data.slides;
            noImages = id_data.images;
            gridSize = id_data.gridSize;
            currentSlide = 0;

            // Re-attach the imagedrop-size element since it's been loaded from the server again
            $id_s = $("#imagedrop-size");
        });
    }

    // Helper function to load the contents of a slide
    //
    function load_slide(slide) {

        var data = {action: 'id_load_images', slide: slide};

        jQuery.post(ajaxurl, data, function (response) {
            $(".slide-" + data.slide).html(response).addClass("loaded");
        });
    }

    // Safe handler for .prop() using jQuery 1.6+ falling back to .attr()
    function safe_prop(selector, key, value) {
        return (typeof($.prop) !== "undefined") ?
            $(selector).attr(key, value).prop(key, value) :
            $(selector).attr(key, value);
    }

    $(document).ready(function () {

        // If id_data isn't defined the imagedrop meta box is not loaded on this page.
        if (typeof(id_data) === "undefined") {
            return;
        }

        $id_s = $("#imagedrop-size");

        // Read the number of slides and images stored in the id_data object
        noSlides = id_data.slides;
        noImages = id_data.images;
        gridSize = id_data.gridSize;

        // Start the resize polling
        resize_id();

        // Start the new image polling
        new_image_poll_id = setTimeout(new_image_poll, 30000);

        // Handler for the .slide-prev button
        $(document).on('click', ".slide-prev", function () {

            // Hide all slides and then show the current one
            $(".slide").addClass("hidden");
            $(".slide-" + --currentSlide).removeClass("hidden");

            // Enable/disable next/prev buttons accordingly
            safe_prop(".slide-next", 'disabled', '');

            if (currentSlide === 0) {
                safe_prop(".slide-prev", 'disabled', 'disabled');
            }

            return false;
        });

        // Handler for the .slide-next button
        $(document).on('click', ".slide-next", function () {

            currentSlide++;

            // Hide all slides and then show the current one
            $(".slide").addClass("hidden");
            $(".slide-" + currentSlide).removeClass("hidden");

            // Enable/disable next/prev buttons accordingly
            safe_prop(".slide-prev", 'disabled', '');

            if ((currentSlide + 1) === noSlides) {
                safe_prop(".slide-next", 'disabled', 'disabled');
            }

            // If the div doesn't have .loaded the content has to be loaded with an ajax call
            if (!$(".slide-" + currentSlide).hasClass("loaded")) {
                load_slide(currentSlide);
            }

            return false;
        });

        $(document).on('click', "#imagedrop-search-button", function () {

            var query = $("#imagedrop-search").val(),
                data = {action: 'id_search_images', query: query };

            // Remove all the loaded slides and disable the next/prev buttons
            $(".slide").remove();
            safe_prop(".slide-prev, .slide-next", 'disabled', 'disabled');
            currentSlide = 0;

            // Load a new empty slide from the loading template
            $.tmpl(loading_template, {slide: 0}).removeClass("hidden").appendTo("#slides-wrapper");

            // If an empty search has been performed, just start the polling again and refresh
            // the content entirely from the server
            //
            if (query.length === 0) {

                if (new_image_poll_id === null) {
                    new_image_poll();
                }
                return false;
            }

            // For searches with keywords
            //
            // Stop new image polling since we've done a search
            clearTimeout(new_image_poll_id);
            new_image_poll_id = null;

            // Request data from the server
            $.post(ajaxurl, data, function (response) {

                // Create a new detached div to store the images from the response
                // This is needed in order to provide pagination
                //
                var $tmp = $("<div></div>").html(response);
                var images = $tmp.find("img");

                // Only one slide needed, just put in the contents and set the internal variables
                if (images.length < gridSize) {
                    $(".slide-0").html(response).addClass("loaded");
                    noImages = images.length;
                    noSlides = 1;

                }
                // More than one sleed needed, new slides will be loaded from the loading template and
                // images will be added to according to the gridSize
                //
                else {

                    $(".slide-0").html("");

                    var currSlide = 0;

                    for (var i = 0; i < images.length; i++) {
                        if (i > 0 && (i % gridSize) === 0) {
                            $.tmpl(loading_template, {slide: ++currSlide }).
                                addClass("loaded").
                                html("").
                                appendTo("#slides-wrapper");
                        }

                        // Add whitespace after the image to get some margin between the images
                        $(".slide-" + currSlide).append(images[i]).append(" ");
                    }

                    // Enable the next button and update the internal variables
                    safe_prop(".slide-next", 'disabled', '');
                    noImages = images.length;
                    noSlides = (currSlide + 1);
                }
            });

            return false;
        });
    });
})(jQuery);
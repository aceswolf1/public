/**
 * Hello World: Step 1
 *
 * Simple block, renders and saves the same content without interactivity.
 *
 * Using inline styles - no external stylesheet needed.  Not recommended
 * because all of these styles will appear in `post_content`.
 */
(function (blocks, components, i18n, element) {
  var el = element.createElement;
  var __ = i18n.__;

  var blockStyle = {
    backgroundColor: '#fff',
    border: '1px solid #aaa',
    color: '#555',
    padding: '20px',
  };

  blocks.registerBlockType('dearpdf/viewer', {
    title: __('DearPDF: PDF Viewer', 'dearpdf-viewer'),
    icon: "list-view",
    category: "common",
    supports: {customClassName: !!1, html: !!1},
    example: {},
    attributes: {
      alignment: {
        type: 'string',
        default: 'center',
      },
      id: {
        type: 'integer',
      },
      isFlipbook: {
        type: "boolean",
        default: false
      },
      source: {
        type: 'string',
        default: ''
      },
      filename: {
        type: 'string',
        default: '',
      }
    },
    edit: function (props) {
      var focus = props.focus;
      var attributes = props.attributes;
      var alignment = props.attributes.alignment;

      var onSelectPDF = function (media) {
        return props.setAttributes({
          source: media.url,
          id: media.id,
          filename: media.filename
        });
      };

      function onChangeAlignment(newAlignment) {
        props.setAttributes({alignment: newAlignment});
      }

      function onChange(event) {
        props.setAttributes({id: event.target.value});
      }

      console.log(props);
      return [
        !!focus && el( // Display controls when the block is clicked on.
          blocks.BlockControls,
          {key: 'controls'},
          el(
            blocks.AlignmentToolbar,
            {
              value: alignment,
              onChange: onChangeAlignment,
            }
          ),
        ),
        el(
          'div',
          {
            className: props.className,
            style: blockStyle,
          },
          el(
            'div', {}, 'DearPDF Viewer'),
          el(wp.editor.MediaUpload, {
              onSelect: onSelectPDF,
              type: 'application/pdf',
              value: attributes.id,
              render: function (obj) {
                return el(components.Button, {
                    className: attributes.id ? 'image-button' : 'button button-large',
                    onClick: obj.open
                  },
                  !attributes.id ? i18n.__('Select PDF') : el('div', {}, "PDF File: " + attributes.filename)
                );
              }
            },
          ),
          el(
            'div', {}, "isFlipbook:" + (attributes.isFlipbook ? "true" : "false")),
          el(
            'div',null,
          )
        ),
      ];
    },
    save: function (props) {
      var attributes = props.attributes;

      var attr = {
        'id': attributes.id,
        'display': attributes.display,
        'source': attributes.id ? null : attributes.source,
        'isflipbook': attributes.isFlipbook
      };

      var shortcode = "[dearpdf";
      Object.keys(attr).forEach(function (key) {
        if (attr[key]) shortcode += (' ' + key + '="' + attr[key] + '"')
      });
      shortcode += ']';
      return shortcode;
    }

  });


})(window.wp.blocks, window.wp.components, window.wp.i18n, window.wp.element);

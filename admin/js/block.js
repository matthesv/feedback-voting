( function( blocks, element, blockEditor, components ) {
    var el = element.createElement;
    var registerBlockType = blocks.registerBlockType;
    var InspectorControls = blockEditor.InspectorControls;
    var PanelBody = components.PanelBody;
    var TextControl = components.TextControl;
    var ToggleControl = components.ToggleControl;
    var SelectControl = components.SelectControl;

    registerBlockType( 'feedback-voting/module', {
        title: 'Feedback Voting',
        icon: 'thumbs-up',
        category: 'widgets',
        attributes: {
            question: { type: 'string', default: 'War diese Antwort hilfreich?' },
            showScore: { type: 'boolean', default: true },
            scoreLabel: { type: 'string', default: 'Euer Score' },
            scoreAlignment: { type: 'string', default: 'left' },
            scoreWrap: { type: 'string', default: 'none' },
            scoreLabelPosition: { type: 'string', default: 'top' },
            schemaRating: { type: 'boolean', default: true },
            schemaType: { type: 'string', default: 'Product' }
        },
        edit: function( props ) {
            var at = props.attributes;
            function update( key ) {
                return function( value ) {
                    var obj = {}; obj[ key ] = value; props.setAttributes( obj );
                };
            }
            return [
                el( InspectorControls, {},
                    el( PanelBody, { title: 'Einstellungen', initialOpen: true },
                        el( TextControl, {
                            label: 'Frage',
                            value: at.question,
                            onChange: update('question')
                        } ),
                        el( ToggleControl, {
                            label: 'Score anzeigen',
                            checked: at.showScore,
                            onChange: update('showScore')
                        } ),
                        at.showScore && el( TextControl, {
                            label: 'Score Text',
                            value: at.scoreLabel,
                            onChange: update('scoreLabel')
                        } ),
                        at.showScore && el( SelectControl, {
                            label: 'Ausrichtung',
                            value: at.scoreAlignment,
                            options: [
                                { label: 'links', value: 'left' },
                                { label: 'zentriert', value: 'center' },
                                { label: 'rechts', value: 'right' }
                            ],
                            onChange: update('scoreAlignment')
                        } ),
                        at.showScore && el( SelectControl, {
                            label: 'Textumfluss',
                            value: at.scoreWrap,
                            options: [
                                { label: 'keiner', value: 'none' },
                                { label: 'links', value: 'left' },
                                { label: 'rechts', value: 'right' }
                            ],
                            onChange: update('scoreWrap')
                        } ),
                        at.showScore && el( SelectControl, {
                            label: 'Label-Position',
                            value: at.scoreLabelPosition,
                            options: [
                                { label: 'Oben', value: 'top' },
                                { label: 'Unten', value: 'bottom' }
                            ],
                            onChange: update('scoreLabelPosition')
                        } ),
                        el( ToggleControl, {
                            label: 'Snippets ausgeben',
                            checked: at.schemaRating,
                            onChange: update('schemaRating')
                        } ),
                        el( SelectControl, {
                            label: 'Schema-Typ',
                            value: at.schemaType,
                            options: [
                                { label: 'Book', value: 'Book' },
                                { label: 'Course', value: 'Course' },
                                { label: 'Event', value: 'Event' },
                                { label: 'LocalBusiness', value: 'LocalBusiness' },
                                { label: 'Movie', value: 'Movie' },
                                { label: 'Product', value: 'Product' },
                                { label: 'Recipe', value: 'Recipe' },
                                { label: 'SoftwareApplication', value: 'SoftwareApplication' }
                            ],
                            onChange: update('schemaType')
                        } )
                    )
                ),
                el( 'p', {}, 'Feedback Voting Modul' )
            ];
        },
        save: function() { return null; }
    } );
} )( window.wp.blocks, window.wp.element, window.wp.blockEditor || window.wp.editor, window.wp.components );

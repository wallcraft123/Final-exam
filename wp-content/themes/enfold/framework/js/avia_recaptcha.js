var alert = [];

var aviaRecaptchaAlert = function( button ) {
    button.addEventListener( 'click', function( event ) {    
        var disabled = event.target.classList.contains( 'avia-button-default-style' );
        if( disabled ) {
            event.preveDefault();
            alert( 'Are you human? Verify with reCAPTCHA first.' );
        }  
    });
}

var aviaRecaptchaSetTokenName = function( form, form_id ) {
    var n = form.getElementsByTagName( 'input' );
    var t = n[0].getAttribute( 'value' );
    var d = n[0].getAttribute( 'id' );
    var v = null;
    
    if( d == 'avia_1_' + form_id ) {
        n[0].setAttribute( 'name', 'avia_label_input' );
        v = n[0].value;
    };   

    return v;
}
		
var aviaRecaptchaDetectHuman = function( form, action ) {
    document.body.addEventListener( 'mousemove', function( event ) {
        form.setAttribute( 'action', action );
    }, false );

    document.body.addEventListener( 'touchmove', function( event ) {
        form.setAttribute( 'action', action );
    }, false );

    document.body.addEventListener( 'keydown', function( event ) {
        if ( ( event.keyCode === 9 ) || ( event.keyCode === 13 ) ) {
            form.setAttribute( 'action', action );
        }
    }, false );
}

var aviaRecaptchaNotice = function( el, notice ) {
    var p = document.createElement( 'p' );
    var t = document.createTextNode( notice );
    p.appendChild( t );
    p.classList.add( 'g-recaptcha-notice' );                           
    el.parentNode.insertBefore( p, el );
};

var aviaRecaptchaPlaceholder = function( el ) {
    var p = document.createElement( "p" );
    p.classList.add( 'g-recaptcha-widget' );                           
    el.parentNode.insertBefore( p, el );
};

var aviaRecaptchaLogoRemove = function() {
    var logo = document.querySelectorAll( '.grecaptcha-badge' );
    if( logo ) {
        for ( var i = 0; i < logo.length; i++ ) {
            logo[ i ].style.display = 'none';
        }  
    }
}

var aviaRecaptchaRender = function() {
    var forms = document.getElementsByTagName( 'form' );	

    for ( var i = 0; i < forms.length; i++ ) {

        var mailchimp = forms[ i ].classList.contains( 'avia-mailchimp-form' );

        if ( ! mailchimp ) {
            var action = forms[ i ].getAttribute( 'action' );
            forms[ i ].removeAttribute( 'action' );
        }
       
        if ( forms[ i ].classList && forms[ i ].classList.contains( 'av-form-recaptcha' ) && ! mailchimp ) {	
            var submit = forms[ i ].querySelector( 'input[type="submit"]' );
            var form_id = forms[ i ].getAttribute( 'data-avia-form-id' );
            var sitekey = submit.getAttribute( 'data-sitekey' );
            var notice = submit.getAttribute( 'data-notice' );
            var size = submit.getAttribute( 'data-size' );
            var theme = submit.getAttribute( 'data-theme' );
            var tabindex = submit.getAttribute( 'data-tabindex' );
            var version = submit.getAttribute( 'data-vn' );
            var callback = submit.getAttribute( 'data-callback' );
            var expired = submit.getAttribute( 'data-expired-callback' );
            var label = aviaRecaptchaSetTokenName( forms[ i ], form_id );
            
            submit.value = "Authenticating...";
     
            aviaRecaptchaPlaceholder( submit );
            if( notice != null ) aviaRecaptchaNotice( submit, aviaRecaptchaTextDecode( notice ) );
            aviaRecaptchaDetectHuman( forms[ i ], action );
            
            submit.classList.add( 'avia-button-default-style' );

            aviaRecaptchaAlert( submit );
            
            var params = {
                'sitekey': sitekey,
                'size': size,
                'theme': theme,
                'tabindex': tabindex,
            };
     
            var placeholder = forms[ i ].querySelector( '.g-recaptcha-widget' );

            if ( version == 'v2' && callback && 'function' == typeof window[ callback ] ) {
                params[ 'callback' ] = window[ callback ];
            }

            if ( version == 'v2' && expired && 'function' == typeof window[ expired ] ) {
                params[ 'expired-callback' ] = window[ expired ];
            }

            if ( version == 'v2' ) {
                grecaptcha.render( placeholder, params );
            } else {
                grecaptcha.ready(function() {
                    grecaptcha.execute( sitekey, { action: 'load' } ).then( function( token ) {            
                        aviaRecaptchaSuccess( token );                  
                    });
                });
            }

            forms[ i ].setAttribute( 'data-widget-id', form_id );   
            alert.push( label );          
        }
    }
};

var aviaRecaptchaSuccess = function( token ) {
    if( ! token ) return;
    aviaRecaptchaVerify( token, alert );
};

var aviaRecaptchaVerify = function( token, alert ) {
    if( ! token ) return;

    jQuery.ajax( {
        type: "POST",
        url: avia_framework_globals.ajaxurl,
        data: {
            g_recaptcha_response: token,
            g_recaptcha_nonce: avia_recaptcha.verify_nonce,
            g_recaptcha_alert: alert,
            action: 'avia_ajax_recaptcha_verify',    
        },
        success: function( response ) {  
            var results = JSON.parse( response ); 

            if ( results.success == false ) return;

            // todo: reverify if score is less than the allowed threshold
            // if ( results.score < 0.5 && vn == 'v3' ) {
            //     grecaptcha.render( placeholder, params );
            //     return;
            // }

            var forms = document.querySelectorAll( '.av-form-recaptcha' );
            var notices = document.getElementsByClassName( 'g-recaptcha-notice' );
            var widgets = document.getElementsByClassName( 'g-recaptcha-widget' );    
            var buttons = document.querySelectorAll( 'input[type="submit"]' );

            for ( var i = 0; i < buttons.length; i++ ) {
                if( buttons[ i ].classList.contains( 'avia-button-default-style') ) {
                    buttons[ i ].value = "Submit";
                    buttons[ i ].removeAttribute( 'disabled' );
                    buttons[ i ].classList.remove( 'avia-button-default-style' );  
                }         
            }

            for ( var i = 0; i < notices.length; i++ ) {
                notices[ i ].style.display = 'none';
            }   
            
            for ( var i = 0; i < widgets.length; i++ ) {
                widgets[ i ].style.display = 'none';
            } 

            for ( var i = 0; i < forms.length; i++ ) {
                forms[ i ].classList.remove( 'av-form-labels-style' );
            } 
        },
        error: function() {
        },
        complete: function() {
        }
    } );
}

var aviaRecaptchaExpired = function() {
    grecaptcha.ready(function() {
        grecaptcha.reset();
    });
}

var aviaRecaptchaTextDecode = function( text ) {
    return decodeURIComponent(text.replace(/\+/g, ' '));
}

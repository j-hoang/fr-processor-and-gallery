function parse_query_string(query) {
    var vars = query.split("&");
    var query_string = {};
    for (var i = 0; i < vars.length; i++) {
        var pair = vars[i].split("=");
        var key = decodeURIComponent(pair[0]);
        var value = decodeURIComponent(pair[1]);
        // If first entry with this name
        if (typeof query_string[key] === "undefined") {
            query_string[key] = decodeURIComponent(value);
        // If second entry with this name
        } else if (typeof query_string[key] === "string") {
            var arr = [query_string[key], decodeURIComponent(value)];
            query_string[key] = arr;
        // If third or later entry with this name
        } else {
            query_string[key].push(decodeURIComponent(value));
        }
    }
    return query_string;
}

var collection = (parse_query_string(window.location.search.substring(1))).collection;
console.log(collection);
 


$(document).ready(function() {

    if(typeof collection !== "undefined") {
        $.ajax(
            {
                url: 'initialize_search_field.php',
                data: {collection},
                type: "POST",
            }).done(function(response) 
            {
                if(!response.error) {
                    console.log("(" + response + ")");
                    const keys = JSON.parse(response);
                    if(keys != "") {
                        $('#search').append($("<option></option>").attr("value", '').text(''));
                        keys.forEach(key => {
                            $('#search').append($("<option></option>").attr("value", key.name).text(key.name));
                          });
        
                        $.getScript('chosen/chosen.jquery.min.js', function() {
                            console.debug('Script loaded.');
        
                            $.getScript('chosen/chosen.init.js', function() {
                                console.debug('Script loaded.');
                            });                   
                        }); 
                        
                        $('.navbar-brand').text(collection + ' Gallery');
        
                        $('#search-component').show();        
        
                    } else {
                        $('.navbar-brand').text('No Gallery Found');                
                    }
                }
                
            }).fail(function() 
            {
                alert('ERROR: unable to initialize search field');
            });
    } else {
        $('.navbar-brand').text('No Gallery Found');
    }




    // Ekko Lightbox
    $(document).on('click', '[data-toggle="lightbox"]', function(event) {
        event.preventDefault();
        $(this).ekkoLightbox();
    });

    // Testing Jquery
    console.log('jquery is working!');  
    // search key type event
    $('#search').change(function() {
      if($('#search').val()) {
        let search = $('#search').val();
        $.ajax({
          url: 'gallery_search.php',
          data: {search},
          type: 'POST',
          success: function (response) {
            if(!response.error) {
              let event_images = JSON.parse(response);
              let image_count = Object.keys(event_images).length;
              let i = 0;
              let template = `<div class="row">`;
              event_images.forEach(event_image => {
                template += `
                        <a href="fr_images/${collection}/${event_image.event_image}" data-toggle="lightbox" data-gallery="example-gallery" class="col-sm-4">
                            <img src="fr_images/${collection}/${event_image.event_image}" class="img-fluid mb-4">
                        </a>
                      `;
                i++;

                if(i % 3 === 0)
                    template += `
                        </div>
                        <div class="row">
                        `;

              });
              template += `</div>`;

              let cc_attribution = `
                            <p>"Robert Downey, Jr." by Gage Skidmore is licensed with CC BY-SA 2.0. To view a copy of this license, visit https://creativecommons.org/licenses/by-sa/2.0/<p>
                            <p>"Tony Stark - Robert Downey Jr" by Justin in SD is licensed with CC BY-NC-SA 2.0. To view a copy of this license, visit https://creativecommons.org/licenses/by-nc-sa/2.0/</p>
                            <p>"Mrs H & Robert Downey Jr." by Cormac Heron is licensed with CC BY 2.0. To view a copy of this license, visit https://creativecommons.org/licenses/by/2.0/</p>
                            <p>"Chris Hemsworth" by Gage Skidmore is licensed with CC BY-SA 2.0. To view a copy of this license, visit https://creativecommons.org/licenses/by-sa/2.0/</p>
                            <p>"Chris Hemsworth" by Eva Rinaldi Celebrity Photographer is licensed with CC BY-SA 2.0. To view a copy of this license, visit https://creativecommons.org/licenses/by-sa/2.0/</p>
                            <p>"chris hemsworth ok" by Mario A. P. is licensed with CC BY-SA 2.0. To view a copy of this license, visit https://creativecommons.org/licenses/by-sa/2.0/</p>
                            <p>"Jeremy Renner" by Gage Skidmore is licensed with CC BY-SA 2.0. To view a copy of this license, visit https://creativecommons.org/licenses/by-sa/2.0/</p>
                            <p>"Jeremy Renner" by Eva Rinaldi Celebrity Photographer is licensed with CC BY-SA 2.0. To view a copy of this license, visit https://creativecommons.org/licenses/by-sa/2.0/</p>
                            <p>"Chris Evans & Aaron Taylor-Johnson" by Gage Skidmore is licensed with CC BY-SA 2.0. To view a copy of this license, visit https://creativecommons.org/licenses/by-sa/2.0/</p>
                            <p>"Chris Evans, Scarlett Johansson & Samuel L. Jackson" by Gage Skidmore is licensed with CC BY-SA 2.0. To view a copy of this license, visit https://creativecommons.org/licenses/by-sa/2.0/</p>
                            <p>"Chris Evans, Scarlett Johansson, Samuel L. Jackson & Sebastian Stan" by Gage Skidmore is licensed with CC BY-SA 2.0. To view a copy of this license, visit https://creativecommons.org/licenses/by-sa/2.0/</p>
                            <p>"Scarlett Johansson_004" by GabboT is licensed with CC BY-SA 2.0. To view a copy of this license, visit https://creativecommons.org/licenses/by-sa/2.0/</p>
                            <p>"Samuel L. Jackson" by Gage Skidmore is licensed with CC BY-SA 2.0. To view a copy of this license, visit https://creativecommons.org/licenses/by-sa/2.0/</p>
                            <p>"Sophie Cookson, Colin Firth, Sofia Boutella, Samuel L. Jackson & Taron Egerton" by Gage Skidmore is licensed with CC BY-SA 2.0. To view a copy of this license, visit https://creativecommons.org/licenses/by-sa/2.0/</p>
                            <p>"Cobie Smulders & Samuel L. Jackson" by Gage Skidmore is licensed with CC BY-SA 2.0. To view a copy of this license, visit https://creativecommons.org/licenses/by-sa/2.0/</p>
                            <p>"Samuel L. Jackson" by Nathan Congleton is licensed with CC BY-NC-SA 2.0. To view a copy of this license, visit https://creativecommons.org/licenses/by-nc-sa/2.0/</p>
                            <p>"Robert Downey, Jr., Jeremy Renner, Mark Ruffalo, Chris Hemsworth, Cobie Smulders, Samuel L. Jackson, Chris Evans, Aaron Taylor-Johnson, Paul Bettany & James Spader" by Gage Skidmore is licensed with CC BY-SA 2.0. To view a copy of this license, visit https://creativecommons.org/licenses/by-sa/2.0/</p>
                            <p>"Aaron Taylor-Johnson & Paul Bettany" by Gage Skidmore is licensed with CC BY-SA 2.0. To view a copy of this license, visit https://creativecommons.org/licenses/by-sa/2.0/</p>
                            <p>"Paul Bettany & James Spader" by Gage Skidmore is licensed with CC BY-SA 2.0. To view a copy of this license, visit https://creativecommons.org/licenses/by-sa/2.0/</p>              
                        `;

                template += cc_attribution;                        


              if(template == '') {
                template = `
                       No Matches Found
                      `
              }
              $('#task-result').show();
              $('#gallery').html(template);
            }
          }
        })
      }
    });


  });


 
window.onpopstate = function (e) {
  window.location.reload();
}
  
////////////////////////////////////////
// ThemeBasicProductCatalog

function ThemeBasicProductCatalog() { PluginWAIProductCatalog.call(this); }
ThemeBasicProductCatalog.prototype = Object.create(PluginWAIProductCatalog.prototype);

ThemeBasicProductCatalog.prototype.setCatalogListType = function (type) {
  this.catalogListType = type;

  if (type == 'list') {
    var showType = 'list';
    var hideType = 'grid';
  } else {
    var showType = 'grid';
    var hideType = 'list';
  }

  $('.shopType-' + showType).addClass('active');
  $('.shopType-' + hideType).removeClass('active');

  document.cookie = "catalogListType=" + type;
}


ThemeBasicProductCatalog.prototype.loadNextPage = function () {
  let _this = this;
  _this.page++

  PluginWAIProductCatalog.prototype.loadNextPage(
    _this,
    function (data) {
      let url = new URL(window.location);
      let div = $('<div></div>').html(data).hide();

      $('.tab-content').append(div);
      _this.setCatalogListType(_this.catalogListType);
      div.slideDown();

      url.searchParams.set('page', _this.page);
      window.history.pushState({}, '', url);
    }
  );

  return this;
}

ThemeBasicProductCatalog.prototype.loadPage = function (page) {
  let _this = this;

  switch (page) {
    case '-':
      _this.page -= 1;
    break;
    case '+':
      _this.page += 1;
    break;
    default:
      _this.page = page;
    break;
  }

  PluginWAIProductCatalog.prototype.loadPage(
    _this,
    function (data) {
      let url = new URL(window.location);

      $('.tab-content').html(data).hide().fadeIn();
      _this.setCatalogListType(_this.catalogListType);

      $('html, body').animate({
        scrollTop: $("#productCatalogDefaultContainerDiv").offset().top
      }, 500);

      url.searchParams.set('page', _this.page);
      window.history.pushState({}, '', url);
    }
  );

  return this;
}

ThemeBasicProductCatalog.prototype.setFilter = function() {
  $('#accordionExample a').removeClass('sidebar-active');
  $('#accordionExample span').removeClass('sidebar-active');
}
var ThemeBasicProductCatalog = new ThemeBasicProductCatalog();

////////////////////////////////////////
// ThemeBasicCart

function ThemeBasicCart() { PluginWAICustomerCart.call(this); }
ThemeBasicCart.prototype = Object.create(PluginWAICustomerCart.prototype);

ThemeBasicCart.prototype.updatePrice = function (data) {
  var spolu = (data.itemUpdated['quantity'] * data.itemUpdated['unit_price']).toFixed(2).toString();
  var spolu_text = spolu.replace(".", ",");

  $('#product-subtotal_' + data.itemUpdated['id_product']).text(spolu_text).fadeIn();

  var total_price = (data.totalPrice).toFixed(2).replace(".", ",");
  $('#priceTotal').text(total_price);
}

ThemeBasicCart.prototype.updateHeaderOverview = function (html) {
  $('#minicart').html(html);

  let count = $('#navigationCart li').length;

  $('.cart-info a').fadeOut(function () {
    $('.cart-info a').attr('cart-count', count).fadeIn();
  })
}

ThemeBasicCart.prototype.updateDetailedOverview = function() {
  Surikata.renderPlugin(
    'WAI/Order/CartOverview',
    {},
    function (html) {
      $('.cart-main-area').replaceWith(function() {
        return $(html).hide().fadeIn(1000);
      });
    }
  );
}

ThemeBasicCart.prototype.updateCheckoutOverview = function() {
  let data = this.serializeOrderData();
  data['renderOnly'] = 'orderOverview';

  Surikata.renderPlugin(
    'WAI/Order/Checkout',
    data,
    function (data) {
      $('#order-area')
        .html(data)
        .css('opacity', 1)
      ;
    }
  );
}

ThemeBasicCart.prototype.showMiniCart = function() {
  var miniCart = $("#minicart");

  // Refresh cart items (HERE)

  if (miniCart.hasClass("showing")) {
    miniCart.removeClass("showing");
  }
  else {
    miniCart.addClass("showing");
  }
}

ThemeBasicCart.prototype.placeOrder = function() {
  $('#orderDataForm input').removeClass('required-empty');
  $('#orderDataForm label').removeClass('required-empty');

  PluginWAICustomerCart.prototype.placeOrder(
    function (dataSuccess) {
      if (dataSuccess.status == 'OK') {
        window.location.href = dataSuccess.orderConfirmationUrl;
      }
    },
    function (dataFail) {
      if (dataFail.exception == 'ADIOS\\Widgets\\Orders\\Exceptions\\EmptyRequiredFields') {

        let emptyFields = dataFail.error.split(',');

        $('html, body').animate({
          scrollTop: $('#orderDataForm input[name=' + emptyFields[0] + ']').offset().top
        }, 500);

        for (var i in emptyFields) {
          if (emptyFields[i] == "gdpr_consent" || emptyFields[i] == "general_terms_and_conditions") {
            $('#' + emptyFields[i] + '_label').addClass('required-empty')
          } else {
            $('#orderDataForm input[name=' + emptyFields[i] + ']').addClass('required-empty');
          }
        }
      } else if (dataFail.exception != '') {
        console.log(dataFail);
        $('#unknownErrorDiv').fadeIn();
      }
    }
  );
}

ThemeBasicCart.prototype.showShippingAddress = function() {
  var address = $("#diffShippingAddress");

  var input = $("input[name='differentDeliveryAddress']");

  if (address.hasClass("show")) {
    address.removeClass("show");
    input.val(0);
  }
  else {
    address.addClass("show");
    input.val(1);
  }
}

var ThemeBasicCart = new ThemeBasicCart();

////////////////////////////////////////
// ThemeBasicPopup

var ThemeBasicPopup = {
  show: function () {
    $('#onload-popup').modal('show', {}, 500);
    return this;
  },

  setImage: function (imgSrc) {
    $('#onload-popup .background_bg').css('background-image', 'url(' + imgSrc + ')');
    return this;
  },

  setTitle: function (title) {
    $('#onload-popup .heading_s1 h4').text(title);
    return this;
  },

  setSubTitle: function (subTitle) {
    $('#onload-popup .popup-text > p').text(subTitle);
    return this;
  },

  setConfirmButtonText: function (text) {
    $('#onload-popup .form-group .btn-confirm').text(text);
    return this;
  },

  setConfirmButtonOnclick: function (onclick) {
    $('#onload-popup .form-group .btn-confirm').click(onclick);
    return this;
  },

  setCancelButtonText: function (text) {
    $('#onload-popup .form-group .btn-cancel').text(text);
    return this;
  },
}

////////////////////////////////////////
// ThemeBasicCustomer

function ThemeBasicCustomer() { PluginWAICustomerHome.call(this); }
ThemeBasicCustomer.prototype = Object.create(PluginWAICustomerHome.prototype);

ThemeBasicCustomer.prototype.createAccount = function() {
  $('#registrationForm input').removeClass('required-empty');

  $('#emailIsEmptyOrInvalidErrorDiv').hide();
  $('#accountAlreadyExistsErrorDiv').hide();
  $('#unknownErrorDiv').hide();
  $('#privacyPolicyTermsErrorText').hide();

  if (!$('#privacyPolicyTermsConfirmation').is(':checked')) {
    $('#privacyPolicyTermsErrorDiv').addClass("error");
    $('#privacyPolicyTermsErrorText').show();
  } else {
    $('#privacyPolicyTermsErrorDiv').removeClass("error");
    PluginWAICustomerHome.prototype.createAccount(
      function (dataSuccess) {
        if (dataSuccess.status == 'OK') {
          window.location.href = dataSuccess.registrationConfirmationUrl;
        }        
      },
      function (dataFail) {
        if (dataFail.exception == 'ADIOS\\Widgets\\Customers\\Exceptions\\EmptyRequiredFields') {

          let emptyFields = dataFail.error.split(',');

          for (var i in emptyFields) {
            $('#registrationForm input[name=' + emptyFields[i] + ']').addClass('required-empty');
          }
        } else if(dataFail.exception == 'ADIOS\\Widgets\\Customers\\Exceptions\\EmailIsInvalid') {
          $("#registrationForm input[name=email]").addClass('required-empty');
          $('#emailIsEmptyOrInvalidErrorDiv').fadeIn();
        } else if (dataFail.exception == 'ADIOS\\Widgets\\Customers\\Exceptions\\AccountAlreadyExists') {
          $('#accountAlreadyExistsErrorDiv').fadeIn();
          $("#registrationForm input[name=email]").addClass('required-empty');
        } else {
          $('#unknownErrorDiv').fadeIn();
        }
      }
    );
  }
}

ThemeBasicCustomer.prototype.removeAddress = function (idAddress) {
  PluginWAICustomerHome.prototype.removeAddress(
    {
      'idAddress': idAddress,
      'customerAction': 'removeAddress'
    },
    function(dataSuccess) {
      if (dataSuccess.status == 'OK') {
        location.reload();
      } 
    },
    function(dataFail) {
      console.log(dataFail);
    }
  );
}

ThemeBasicCustomer.prototype.forgotPassword = function () {
  $('#unknownAccount').hide();
  $('#emailIsEmpty').hide();
  $('#emailIsInvalid').hide();
  $('#accountIsNotValidated').hide();
  $('#forgotPasswordDiv input[name=email]').removeClass('required-empty');
  PluginWAICustomerHome.prototype.forgotPassword(
    function(dataSuccess) {
      if (dataSuccess.status == 'OK') {
        $("#forgotPasswordDiv").hide();
        $("#recoveryPasswordSent").fadeIn();
      } 
    },
    function(dataFail) {
      $('#forgotPasswordDiv input[name=email]').addClass('required-empty');
      if (dataFail.exception == "ADIOS\\Widgets\\Customers\\Exceptions\\UnknownAccount") {
        $('#unknownAccount').show();
      } else if(dataFail.exception == "ADIOS\\Widgets\\Customers\\Exceptions\\EmailIsEmpty") {
        $('#emailIsEmpty').show();
      } else if (dataFail.exception == "ADIOS\\Widgets\\Customers\\Exceptions\\EmailIsInvalid") {
        $('#emailIsInvalid').show();
      } else if (dataFail.exception == "ADIOS\\Widgets\\Customers\\Exceptions\\AccountIsNotValidated") {
        $('#accountIsNotValidated').show();
      } else {
        console.log(dataFail);
      }
    }
  );
}

var ThemeBasicCustomer = new ThemeBasicCustomer();

////////////////////////////////////////
// ThemeBasicBlogCatalog

function ThemeBasicBlogCatalog() { PluginWAIBlogCatalog.call(this); }
ThemeBasicBlogCatalog.prototype = Object.create(PluginWAIBlogCatalog.prototype);

var ThemeBasicBlogCatalog = new ThemeBasicBlogCatalog();

////////////////////////////////////////
// ThemeBasicBreadcrumb

function ThemeBasicBreadcrumb() { PluginWAICommonBreadcrumb.call(this); }
ThemeBasicBreadcrumb.prototype = Object.create(PluginWAICommonBreadcrumb.prototype);

var ThemeBasicBreadcrumb = new ThemeBasicBreadcrumb();


/*----------------------------
        Cart Plus Minus Button
    ------------------------------ */
var CartPlusMinus = $(".cart-plus-minus");
CartPlusMinus.prepend('<div class="dec qtybutton">-</div>');
CartPlusMinus.append('<div class="inc qtybutton">+</div>');
$(".qtybutton").on("click", function() {
  var $button = $(this);
  var oldValue = $button.parent().find("input").val();
  if ($button.text() === "+") {
    var newVal = parseFloat(oldValue) + 1;
  } else {
    // Don't allow decrementing below zero
    if (oldValue > 1) {
      var newVal = parseFloat(oldValue) - 1;
    } else {
      newVal = 1;
    }
  }
  $button.parent().find("input").val(newVal);
  $button.parent().find("input").trigger("change");
});
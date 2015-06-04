// FreePBX Search
//
var SearchC = Class.extend({
  init: function() {
    // initialize
    var self = this;
    this.moduleSearch = new Bloodhound({
      datumTokenizer: Bloodhound.tokenizers.obj.whitespace('value'),
      queryTokenizer: Bloodhound.tokenizers.whitespace,
      limit: 10,
      prefetch: {
        ttl: 1000,
        url: window.FreePBX.ajaxurl+'?module=search&command=global',
       filter: function(data) { return $.map(data, function(t) { return { value: t.text, o: t } }); },
      }
    });
    this.moduleSearch.initialize();

    this.itemSearch = new Bloodhound({
      datumTokenizer: Bloodhound.tokenizers.obj.whitespace('value'),
      queryTokenizer: Bloodhound.tokenizers.whitespace,
      limit: 10,
      remote: {
        ttl: 100,
        url: window.FreePBX.ajaxurl+'?module=search&command=local&query=%QUERY&section='+window.FreePBX.params.display,
        filter: function(data) { return $.map(data, function(t) { return { raw: true, value: t.text, o: t } }); },
      },
    });
    this.itemSearch.initialize();

    $('#fpbxsearch .typeahead').typeahead({
      hint: true,
      highlight: true,
      minLength: 1
    }, {
      name: 'extenSearch',
      displayKey: 'value',
      source: this.extMatch(this.getAllExtens()),
    }, {
      name: 'moduleSearch',
      displayKey: 'value',
      source: this.moduleSearch.ttAdapter()
    }, {
      name: 'itemSearch',
      displayKey: 'value',
      source: this.itemSearch.ttAdapter(),
      templates: {
        suggestion: self.genItemHtml,
      }
    })
    .bind("typeahead:selected", function(o,d,n) { self.processSearchClick(o,d,n); })
    .focus();
  },

  extMatch: function(strs) {
    var self = this;
    return function findMatches(q, cb) {
      var matches, oMatches, substrRegex;
      matches = [];
      oMatches = {};
      substrRegex = new RegExp(q, 'i');
      $.each(strs, function(i, str) {
        var val;
        if (substrRegex.test(str)) {
	  // We're an Extension!
	  if (self.extLookup[str] == str) {
	    // It's an extension
	    val = "Extension "+str;
	  } else {
	    val = str+" ("+self.extLookup[str]+")";
	  }
          oMatches[val] = { value: val, ext: str };
        }
      });
      // We loop twice to remove duplicates. This possibly could be
      // quicker by doing a hash check in the previous loop, but it'll
      // be harder to read.
      $.each(oMatches, function(i, obj) { matches.push(obj) });;
      cb(matches);
    };
  },

  getAllExtens: function() {
    var self = this;
    self.extLookup = {};
    var knownExtensions = [];
    if(extmap === null) {
      return knownExtensions;
    }
    $.each(extmap, function(x) {
      if (this.match(/User Exten/)) {
        knownExtensions.push(x);
	self.extLookup[x] = x;
	var extName = this.toString().replace("User Extension: ", "");
        knownExtensions.push(extName);
	self.extLookup[extName] = x;
      }
    });
    return knownExtensions;
  },

  processSearchClick: function(o, d, name) {
    if (name == "moduleSearch") {
      return this.processModuleClick(d.o);
    } else if (name == "extenSearch") {
      return this.processExtenClick(d);
    } else if (name == "itemSearch") {
      return this.processItemClick(d);
    } else {
      console.warn("Not sure what to do with " + name);
      return false;
    }
  },

  processModuleClick: function(o) {
    if (o.type == "get") {
      window.location.search = o.dest;
      return true;
    }
    console.log("No idea what to do with this: ", o);
  },

  processExtenClick: function(o) {
    var ext = this.extLookup[o.ext];
    window.location.search = "?display=extensions&extdisplay="+ext;
    window.location.hash = "";
    return true;
  },

  processItemClick: function(o) {
    var item = o.o;

    var href;

    if(item.dest.match(/\/\//)) {
      // It's an explicit link. (check for // anywhere) Go there.
      href = item.dest;
    } else {
      // It's a relative link. Let's rebuild our URL. Note that IE compat in
      // w.l.origin is in header, where we preload our ajaxurl and modulename
      href = window.location.origin;
      if (item.dest.match(/^\//)) {
        // It starts with a slash
        href += item.dest;
      } else {
        // It's inside freepbx.
        href += window.location.pathname + item.dest;
      }
    }
    if (typeof(item.search) != "undefined") {
      href += "?" + item.search;
    }
    if (typeof(item.hash) != "undefined") {
      href += "#" + item.hash;
    }
    // Actually go there!
    window.location.href = href;
    return true;
  },

  genItemHtml: function(o) {
    if (o.o.type == "text") {
      return "<div>"+o.value+"</div>";
    } else {
      return "<div class='tt-suggestion'><p>"+o.value+"</p></div>";
    }
  },
});

$(document).ready(function() {
  window.Search = new SearchC();
});

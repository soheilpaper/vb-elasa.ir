$.noConflict(true)(function($)
{
	if ($('[name="livewall"]').length == 0)
	{
		// We're not doin nothin
		return false;
	}
		
	var ftd 			= $(document),
		paused 			= false,							// Whether we are are pausing fetching
		idleTime 		= 0,								// Number of seconds we have been idle
		countDown 		= liveWall.liveOptions['refresh'],	// Countdown till next wall refresh, visible in console log
		delay 			= {'status' : 0, 'comment' : 0}		// Countdown till next update is possible
	;
	
	ftd.on('keyup focus', '[name="livewall_textfield"]', function(e)
	{
		var thisEditor = $(this),
			cmd = thisEditor.attr('data-command'),
			contentTypeId = thisEditor.attr('data-contenttypeid'),
			contentId = thisEditor.attr('data-contentid'),
			maxChars = liveWall.liveOptions[cmd + '_maxchars'];
		
		// Check message length
		
		if (maxChars == '0')
		{
			// We're not checking length
			return true;
		}
		
		if (thisEditor.val().length > maxChars)
		{
			// Strip characters that go beyond the limit
			thisEditor.val(thisEditor.val().substring(0, maxChars));
		}
		
		// Set the Remaining Characters count
		$('[name="livewall_remainingchars"][data-command="' + cmd + '"]' + (contentTypeId ? '[data-contenttypeid="' + contentTypeId + '"][data-contentid="' + contentId + '"]' : '')).text((maxChars - thisEditor.val().length));	
		
	}).on('keyup', '[name="livewall_textfield"]', function(e)
	{
		var thisEditor = $(this),
			cmd = thisEditor.attr('data-command'),
			contentTypeId = thisEditor.attr('data-contenttypeid'),
			contentId = thisEditor.attr('data-contentid');
		
		// Prevent any default action from happening
		e.preventDefault();
		
		if (e.which == 13)
		{
			// Save with no hassles
			$('[name="livewall_submit"][data-command="' + cmd + '"]' + (contentTypeId ? '[data-contenttypeid="' + contentTypeId + '"][data-contentid="' + contentId + '"]' : '')).trigger('click');
		}
		
	}).on('click', '[name="livewall_favouritelink"]', function(e)
	{
		var thisEditor = $(this),
			contentTypeId = thisEditor.attr('data-contenttypeid'),
			contentId = thisEditor.attr('data-contentid'),
			thisImage = $('[name="livewall_favouriteimg"]' + (contentTypeId ? '[data-contenttypeid="' + contentTypeId + '"][data-contentid="' + contentId + '"]' : '')),
			thisImageSrc = thisImage.attr('src').substring(thisImage.attr('src').lastIndexOf('/') + 1);
			
		// Replace the source
		thisImage.attr('src', thisImage.attr('src').replace(thisImageSrc, (thisImageSrc == 'favourite-on.png' ? 'favourite-off.png' : 'favourite-on.png')));
		
		var extraParams = {};
		
		if (contentTypeId != '')
		{
			// Set last fetched IDs
			extraParams['contenttypeid'] = contentTypeId;
			extraParams['contentid'] = contentId;
		}
		
		// Fetch shouts nao
		ajaxCall('togglefavourite', extraParams);

	}).on('click', '[name="livewall_submit"]', function(e)
	{
		var thisEditor = $(this),
			cmd = thisEditor.attr('data-command'),
			contentTypeId = thisEditor.attr('data-contenttypeid'),
			contentId = thisEditor.attr('data-contentid'),
			textField = $('[name="livewall_textfield"][data-command="' + cmd + '"]' + (contentTypeId ? '[data-contenttypeid="' + contentTypeId + '"][data-contentid="' + contentId + '"]' : ''));
		
		if (delay[cmd] > 0)
		{
			// Should show an error here
			return false;
		}
		
		// Check message length
		textField.trigger('keyup');
		
		// Set delay
		delay[cmd] = liveWall.liveOptions[cmd + '_delay'];
		
		// Pause this
		paused = true;
		
		var extraParams = {
			'message' : PHP.urlencode($.trim(textField.val()))
		};
		
		if (contentTypeId != '')
		{
			// Set last fetched IDs
			extraParams['contenttypeid'] = contentTypeId;
			extraParams['contentid'] = contentId;
		}
		
		// Fetch shouts nao
		ajaxCall((cmd == 'comment' ? 'savecomments' : 'savestatus'), extraParams);
		
		// Clear the editor
		textField.val('').trigger('keyup');
	}).on('click', '[name="livewall_deletecomment"]', function(e)
	{
		var thisEditor = $(this),
			cmd = thisEditor.attr('data-command'),
			commentId = thisEditor.attr('data-commentid');
		
		if (!confirm(vbphrase['dbtech_livewall_really_delete_comment']))
		{
			// We didn't want to delete
			return true;
		}
		
		// Pause this
		paused = true;
		
		var extraParams = {
			'commentid' : commentId
		};
		
		// Fetch shouts nao
		ajaxCall('deletecomment', extraParams);
	}).on('click', '[name="livewall_deletestatus"]', function(e)
	{
		var thisEditor = $(this),
			cmd = thisEditor.attr('data-command'),
			statusId = thisEditor.attr('data-statusid');
		
		if (!confirm(vbphrase['dbtech_livewall_really_delete_status']))
		{
			// We didn't want to delete
			return true;
		}
		
		// Pause this
		paused = true;
		
		var extraParams = {
			'statusid' : statusId
		};
		
		// Fetch shouts nao
		ajaxCall('deletestatus', extraParams);
	});
		
	// Initial shouts fetcing
	setInterval(function()
	{
		if (delay['status'] > 0)
		{
			// Decrement status delay
			delay['status']--;
		}
		
		if (delay['comment'] > 0)
		{
			// Decrement comment delay
			delay['comment']--;
		}
		
		if (liveWall.liveOptions['refresh'] == 0)
		{
			// We're not doing anything atm
			return;
		}	
		
		if (paused == true)
		{
			// We're not doing anything atm
			return;
		}	
		
		// Increment idle time
		idleTime++;	
		
		/*
		if (idleTime >= liveWall.liveOptions['idletimeout'] && liveWall.liveOptions['idletimeout'] > 0)
		{
			// We're pausing the countdown
			paused = true;
			
			// We're idle
			setMessage(vbphrase['dbtech_vbshout_flagged_idle']
				.replace('%link%', 'return vBShout_unIdle(\'' + instanceId + '\');'),
				'notice',
				instanceId
			);
			
			return;
		}
		*/		
		
		if (--countDown > 0)
		{
			/*
			// Still not fetching :(
			console.log(timeStamp() + vbphrase['dbtech_livewall_fetching_entries_in_x_seconds']
				.replace('%seconds%', countDown)
			);
			*/
		}
		else
		{
			// Init this
			var extraParams = {
				'allids' : {}
			};
			
			$('[name="livewall_commentcount"]').each(function(index, element)
			{
				var thisElem = $(this);
				
				extraParams['allids'][index] = {'contenttypeid' : thisElem.attr('data-contenttypeid'), 'contentid' : thisElem.attr('data-contentid')};
			});
			
			// Fetch shouts nao
			ajaxCall(liveWall.liveOptions['type'], extraParams, 'GET');
		}
			
	}, 1000);
	
	// #########################################################################
	// Shorthand for an ajax call
	function ajaxCall(varname, extraParams, type)
	{
		paused = true;
		
		if (typeof type == 'undefined')
		{
			// Ensure we're setting this correctly
			type = 'POST';
			extraParams['securitytoken'] = SECURITYTOKEN;
		}
		
		// Set additional global params
		extraParams['do'] = 'ajax';
		extraParams['action'] = varname;
		extraParams['sidebar'] = liveWall.liveOptions['sidebar'];
		
		if (typeof liveWall.liveOptions['contenttypeid'] != 'undefined')
		{
			// Set last fetched IDs
			extraParams['contenttypeid'] = liveWall.liveOptions['contenttypeid'];
			extraParams['contentid'] = liveWall.liveOptions['contentid'];
		}		
		
		// Set last fetched IDs
		extraParams['lastids'] = liveWall.lastIds;
		
		// Set userid
		extraParams['userid'] = parseInt(liveWall.userId);

		// Add random garble to avoid IE bug
		extraParams['v'] = Math.random() * 99999999999999;
		
		$.ajax({
			type: type,
			url: 'livewall.php',
			data: (SESSIONURL ? SESSIONURL + '&' : '') + $.param(extraParams)
		})
		.done(function(data)
		{
			// Also reset the countdown here
			paused = false;
			countDown = liveWall.liveOptions['refresh'];
			
			if (typeof data == 'string')
			{			
				try
				{
					// Parse the data
					data = $.parseJSON(data);
				}
				catch (e)
				{
					var errmsg = data;
					data = {'error' : errmsg + "\n\n" + data};
				}
			}
			
			if (data.error)
			{
				// Log the error to the console
				console.error(timeStamp() + "AJAX Error: %s", data.error);
				
				return true;		
			}
			
			if (typeof data.dorefresh != 'undefined')
			{
				// Init this
				var extraParams = {
					'allids' : {}
				};
				
				$('[name="livewall_commentcount"]').each(function(index, element)
				{
					var thisElem = $(this);
					
					extraParams['allids'][index] = {'contenttypeid' : thisElem.attr('data-contenttypeid'), 'contentid' : thisElem.attr('data-contentid')};
				});
				
				// Fetch shouts nao
				ajaxCall(liveWall.liveOptions['type'], extraParams, 'GET');
			}
			
			if (typeof data.commentid != 'undefined')
			{
				$('[name="livewall_commentwrapper"][data-commentid="' + data.commentid + '"]').fadeOut('fast').promise().done(function()
				{
					// Remove this element from the DOM
					$(this).remove();
				});
			}
			
			if (typeof data.statusid != 'undefined')
			{
				$('[name="livewall_entrywrapper"][data-contenttypeid="statusupdate"][data-contentid="' + data.statusid + '"]').fadeOut('fast').promise().done(function()
				{
					// Remove this element from the DOM
					$(this).remove();
				});
			}
			
			if (typeof data.lastids != 'undefined')
			{
				liveWall.lastIds = data.lastids;
			}
			
			if (typeof data.entries != 'undefined')
			{
				for (var i in data.entries)
				{
					// Display the HTML
					$('[name="livewall"]').prepend(data.entries[i]);
					$('[name="livewall"] > ' + (liveWall.liveOptions['sidebar'] == '1' ? 'li' : 'div')).first().fadeIn('fast');
				}
				
				if (liveWall.liveOptions['perpage'] != '-1')
				{
					var len = $('[name="livewall"] > ' + (liveWall.liveOptions['sidebar'] == '1' ? 'li' : 'div')).length;
					while (len > liveWall.liveOptions['perpage'])
					{
						// Hide last child
						$('[name="livewall"] > ' + (liveWall.liveOptions['sidebar'] == '1' ? 'li' : 'div')).last().fadeOut('fast').promise().done(function()
						{
							// Remove this element from the DOM
							$(this).remove();
						});
						
						len--;
					}
				}
			}
			
			if (typeof data.allids != 'undefined')
			{
				for (var i in data.allids)
				{
					// Set the new comment count
					$('[name="livewall_commentcount"][data-contenttypeid="' + data.allids[i]['contenttypeid'] + '"][data-contentid="' + data.allids[i]['contentid'] + '"]').text(data.allids[i]['commentcount']);
				}
			}			
			
			if (typeof data.comments != 'undefined')
			{
				for (var contentTypeId in data.comments)
				{
					for (var contentId in data.comments[contentTypeId])
					{
						// Shorthand
						var commentsField = $('[name="livewall_comments"][data-contenttypeid="' + contentTypeId + '"][data-contentid="' + contentId + '"]');
						
						// Empty this out
						commentsField.empty();
						
						for (var i in data.comments[contentTypeId][contentId])
						{
							// Set the new comment count
							commentsField.append(data.comments[contentTypeId][contentId][i]);
						}
					}
				}
			}			
		})
		.fail(function(data, textStatus)
		{
			// Also reset the countdown here
			paused = false;
			countDown = liveWall.liveOptions['refresh'];
			
			try
			{
				if (data.statusText == 'communication failure' || data.statusText == 'transaction aborted' || data.status == 0)
				{
					// Ignore this error
					return false;
				}
				
				// Log the error to the console
				console.error(timeStamp() + "AJAX Error: Status = %s: %s", data.status, data.statusText);
			}
			catch (e)
			{
				// Log the error to the console
				console.error(timeStamp() + "AJAX Error: %s", data.responseText);
			}
		})
		;
	};
	
	// #########################################################################
	// Debugging function, generates a timestamp of when something occurred
	function timeStamp()
	{
		var d = new Date();
		
		return '[' + d.getHours() + ':' + d.getMinutes() + ':' + d.getSeconds() + '] ';
	};	
});
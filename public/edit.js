window.addEvent('domready', function() {

	$$('.group--contents-inherit > .control-group a[href="#edit"]').each(function(el) {

		el.addEvent('click', function(ev) {

			ev.stop()
			el.getParent('.control-group').toggleClass('editing')

		})
	})
})

window.addEvent('brickrouge.update', function() {

	var selector = $(document.body).getElement('[name="template"]')
	, form
	, req

	if (!selector) return

	form = selector.form

	if (selector.retrieve('loader')) return

	selector.store('loader', true)

	req = new Request.Element
	({
		url: '/api/pages/template-editors',

		onSuccess: function(el)
		{
			var previous_hiddens = form.getElements('input[type=hidden][name^="contents["][name$="editor]"]')
			, container = form.getElement('.group--contents')
			, inheritContainer = form.getElement('.group--contents-inherit')

			previous_hiddens.destroy()

			el.getChildren('input[type="hidden"]').each
			(
				function(input)
				{
					form.adopt(input);
				}
			)

			container.getChildren('.control-group').each
			(
				function(group)
				{
					if (group.hasClass('control-group--template')) return

					group.destroy()
				}
			)

			if (inheritContainer)
			{
				inheritContainer.destroy()
			}

			el.getElements('.group--contents .control-group').each
			(
				function(group)
				{
					console.log('group:', group);

					if (group.hasClass('control-group--template')) return

					container.adopt(group);
				}
			)

			inheritContainer = el.getElement('.group--contents-inherit')

			if (inheritContainer)
			{
				inheritContainer.inject(container, 'after')
			}

			Brickrouge.updateDocument(form)
			document.fireEvent('editors')
		}
	})

	selector.addEvent
	(
		'change', function(ev)
		{
			var pageId = form.elements[ICanBoogie.Operation.KEY] ? form.elements[ICanBoogie.Operation.KEY].value : null

			req.get({ page_id: pageId, template: selector.get('value') })
		}
	)
})

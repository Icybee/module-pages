const WdDroppableTableRow = new Class
({
	initialize: function(el)
	{
		this.element = document.id(el)
	},

	getParent: function()
	{
		const level = this.getLevel()
		let parent = this.element.getPrevious()

		for ( ; parent ; parent = parent.getPrevious())
		{
			let parent_level = this.getLevel(parent)

			if (parent_level < level) return parent
		}
	},

	getDirectChildren: function()
	{
		const children = []
		const level = this.getLevel()
		let child = this.element.getNext()

		for ( ; child ; child = child.getNext())
		{
			let child_level = this.getLevel(child)

			//console.info('getDirectChildren:> level: %d, child_level: %d', level, child_level);

			if (child_level <= level) break;
			if (child_level > level + 1) continue;

			children.push(new WdDroppableTableRow(child))
		}

		//console.log('get children for %a: %a', this.element, children);

		return children
	},

	inject: function(target, how)
	{
		//console.info('inject: %a %s', target, how);
	},

	getLevel: function(el)
	{
		if (!el)
		{
			el = this.element
		}

		return el.getElements('div.indentation').length
	},

	setLevel: function(level)
	{
		/* update our children */

		this.getDirectChildren().each
		(
			function(child)
			{
				child.setLevel(level + 1)
			}
		);

		/* update our level */

		const indentations = this.element.getElements('div.indentation')
		let diff = level - indentations.length;

		if (diff < 0)
		{
			while (diff++)
			{
				indentations.pop().destroy()
			}
		}
		else if (diff > 0)
		{
			const target = this.element.getElement('div.handle')

			while (diff--)
			{
				new Element('div.indentation', { 'html': '&nbsp;' }).inject(target, 'before')
			}
		}
	},

	getParentIdElement: function(el)
	{
		if (!el)
		{
			el = this.element;
		}

		return el.getElement('input[name^=parents]');
	},

	getParentId: function(el)
	{
		return this.getParentIdElement(el).value;
	},

	setParent: function(parent)
	{
		this.getParentIdElement().value = parent ? parent.id.substr(4) : 0;
	},

	/**
	 * check parent adoption while moving items, updating parents as needed :
	 *
	 * - the element after the target is a child of the target, the dragged elements
	 * become children of the target element
	 *
	 * - the element after the target element has a higher level, the dragged elements
	 * can become children of their previous sibling
	 *
	 */
	updateParent: function()
	{
		const level = this.getLevel()
		let parent = this.element.getPrevious();

		for ( ; parent ; parent = parent.getPrevious())
		{
			const parent_level = this.getLevel(parent);

			//console.log('level: %d, previous level: %d (%a)', level, previous_level, previous);

			if (parent_level >= level)
			{
				continue;
			}

			//console.log('found parent: %a', previous);

			break;
		}

		this.setParent(parent);
	}
})

const WdDraggableTableRow = new Class
({
	Extends: WdDroppableTableRow,

	initialize: function(el)
	{
		this.parent(el);

		this.handle = this.element.getElement('div.handle');

		this.handle.addEvents
		({
			mousedown: function(ev)
			{
//				ev.stop();

				this.dragStart();
			}
			.bind(this),

			click: function(ev)
			{
				ev.stop();
			}
		});
	},

	dragStart: function()
	{
		this.mouseup_callback = this.dragFinish.bind(this)
		this.mousemove_callback = this.dragQuery.bind(this)

		//console.info('drag start with: %a', this.element);

		document.body.addEvent('mouseup', this.mouseup_callback)
		document.body.addEvent('mousemove', this.mousemove_callback)

		/* search children */

		var level = this.getLevel()
		, child = this.element.getNext()

		//console.log('level: %d', level);

		this.dragged = [ new WdDroppableTableRow(this.element) ]

		for ( ; child ; child = child.getNext())
		{
			var child_level = child.getElements('div.indentation').length

			if (child_level <= level)
			{
				break;
			}

			this.dragged.push(new WdDroppableTableRow(child))
		}

		this.dragged.each(function(el) {

			el.element.addClass('dragged')
		})

		//console.log('%d elements dragged', this.dragged.length);
	},

	dragQuery: function(ev)
	{
		/*
		 * handle level
		 */

		var box = this.handle.getCoordinates()

		if (ev.page.x < box.left)
		{
			this.changeLevel(-1)
		}
		else if (ev.page.x > box.left + box.width)
		{
			this.changeLevel(1)
		}

		/*
		 * handle weight
		 */

		var coords = this.element.getCoordinates()
		, y = coords.top
		, h = coords.height

		//console.log('dragQuery: el: %a, %a (%a), coords: %a', this.element, ev, ev.page, coords);

		if (ev.page.y < y)
		{
			this.changeWeight(-1)
		}
		else if (ev.page.y > y + h)
		{
			this.changeWeight(1)
		}
	},

	dragFinish: function(ev)
	{
		//console.log('dragFinish: %a', ev);

		/* remove event listeners */

		document.body.removeEvent('mouseup', this.mouseup_callback)
		document.body.removeEvent('mousemove', this.mousemove_callback)

		/* remove the 'dragged' class upon children */

		if (!this.dragged) return

		this.dragged.each(function(el) {

			el.element.removeClass('dragged')
		})

		this.dragged = null
	},

	changeWeight: function(slide)
	{
		switch (slide)
		{
			case -1:
			{
				var target = this.element.getPrevious()

				if (!target) return

				var level = this.getLevel()
				, parent = target.getPrevious()
				, parent_level = parent ? this.getLevel(parent) : 0

				if (level - 1 > parent_level)
				{
					this.setLevel(parent_level + 1)
				}

				target.inject(this.dragged.getLast().element, 'after')
			}
			break;

			case 1:
			{
				var target = this.dragged.getLast().element.getNext();

				if (!target || !target.hasClass('draggable'))
				{
					return;
				}

				var level = this.getLevel();
				var target_level = this.getLevel(target);

				//console.log('after: %a with level %d, my level is %d', target, target_level, level);

				/*
				if (level == target_level || level - 1 > target_level)
				{
					this.setLevel(target_level + 1);
				}
				*/

				/* on déplace l'élement en premier dans un arbre */

				var next = target.getNext();

				if (next && level < this.getLevel(next))
				{
					this.setLevel(target_level + 1);
				}

				target.inject(this.element, 'before');
			}
			break;

			default:
			{
				return;
			}
			break;
		}

		this.modified()
	},

	/**
	 * Change the level for the selected elements :
	 *
	 * * the element has no parent, the level is not changed
	 *
	 * * the element can be moved deeper if the previous element has the same or deeper level,
	 * its maximum depth can only be one deeper.
	 *
	 * * the element can be moved shallower if the next element has a shallower level
	 *
	 */
	changeLevel: function(slide)
	{
		var level = this.getLevel()

		switch (slide)
		{
			case -1:
			{
				if (level == 0) return

				var next = this.dragged.getLast().element.getNext()

				if (next)
				{
					var next_level = this.getLevel(next)

					if (next_level >= level) return
				}
			}
			break

			case 1:
			{
				var previous = this.element.getPrevious()

				if (!previous) return

				var previous_level = this.getLevel(previous)

				if (previous_level < level) return
			}
			break

			default:
			{
				throw 'slide value "' + slide + '" is not implemented'
			}
			break
		}

		this.setLevel(level + slide)
		this.modified()
	},

	modified: function()
	{
		this.updateParent()

//		Icybee.actionbar.display('update-tree')
		Brickrouge.from(document.body.getElement('.actionbar-actions--update-tree')).show()

		if (this.element.getElement('sup.modified')) return

		this.element.addClass('modified')

		var target = this.element.getElement('a.edit')
		, mark = new Element('sup', { 'class': 'modified', 'html': '*' })

		mark.inject(target, 'after')
	}
})

!function() {

	var manager
	, initialOrder = null
	, initialRelation = null // TODO-20130715

	function update()
	{
		// handles

		var handles = manager.element.getElements('div.handle')

		if (handles.length)
		{
			manager.element.getElements('tr.draggable').each(function(el) {

				new WdDraggableTableRow(el)

			})
		}

		// highlight

		manager.element.getElements('tr.volatile-highlight').each(function (el) {

			el.set('tween', { duration: 2000, transition: 'sine:out' })
			el.highlight('#FFE')

			;( function() { el.setStyle('background-color', ''); el.removeClass('volatile-highlight') } ).delay(2100)
		})
	}

	window.addEvent('icybee.manageblock.ready', function(m) {

		manager = m
		manager.addEvent('update', update)

		update()

		manager.element.addEvent('click:relay(td.cell--is-navigation-excluded i)', function(ev, el) {

			el.toggleClass('on')

			new Request.API({

				url: 'pages/' + el.get('data-nid') + '/is-navigation-excluded',

				onFailure: function(response) {

					el.toggleClass('on')

				},

				onSuccess: function(response) {

					el.fireEvent('change', {})

				}

			})[el.hasClass('on') ? 'put' : 'delete']()
		})
	})

	const ActionBarUpdateTree = new Class({

		initialize: function(el, options)
		{
			this.element = el = document.id(el)
			this.wrapper = new Element('div.actionbar-actions-wrapper')
			this.wrapper.wraps(el)

			el.addEvent('click:relay(.btn-primary)', this.process.bind(this))
			el.addEvent('click:relay([data-dismiss])', this.cancel.bind(this))

		},

		show: function()
		{
			this.wrapper.addClass('show')
		},

		hide: function()
		{
			this.wrapper.removeClass('show')
		},

		/**
		 * The initial order of the rows is restored when the `cancel` action is triggered.
		 */
		cancel: function()
		{
			var body = manager.element.getElement('tbody')

			initialOrder.each(function(tr) {

				var mark = tr.getElement('sup.modified')

				if (mark) mark.destroy()

				tr.removeClass('modified')

				body.appendChild(tr)

			})

			initialOrder = null

			this.hide()
		},

		/**
		 * Saves the modification applied to the tree when the `save` action is triggered.
		 */
		process: function()
		{
			var order = [],
			relation = {}
			, body = manager.element.getElement('tbody')

			body.getElements('input[name^=parents]').each(function(el) {

				var nid = el.name.match(/(\d+)/)[0]

				order.push(nid)
				relation[nid] = el.value
			})

			new Request.API
			({
				url: 'pages/update_tree',

				onRequest: function()
				{
					this.element.addClass('working')
				}
				.bind(this),

				onComplete: function()
				{
					this.element.removeClass('working')
				}
				.bind(this),

				onSuccess: function(response)
				{
					body.getElements('tr.modified').each(function(tr) {

						tr.removeClass('modified')
						tr.getElement('sup.modified').destroy()
					})

					initialOrder = null

					this.hide()
				}
				.bind(this)

			}).post({ order: order, relation: relation })
		}

	})

	document.body.addEvent('mousedown:relay(.listview .handle)', (ev, el) => {

		if (initialOrder !== null)
		{
			return
		}

		initialOrder = el.getParent('tbody').getElements('tr')

	})

	Brickrouge.register('ActionBarUpdateTree', (element, options) => new ActionBarUpdateTree(element, options))

} ()

exports = window.theses ||= {};

exports.QuickPostForm = React.createClass
    navEntries:
        status:
            icon: "comment-o"
            label: "Status"
        link:
            icon: "link"
            label: "Link"
        photo:
            icon: "picture-o"
            label: "Photo"
    getInitialState: ->
        return postType: undefined
    setPostType: (type) ->
        @setState postType: type
    onSubmit: ->
        false
    render: ->
        R = React.DOM

        R.form onSubmit: @onSubmit,
            R.nav className: "post-type",
                "Post something:"
                R.ul null,
                    for id, entry of @navEntries
                        R.li {key: id, className: if @state.postType is id then "active" else ""},
                          R.a href:"#", onClick: @setPostType.bind(this, id),
                                R.i className: "fa fa-#{entry.icon}"
                                R.span null, entry.label
            if @state.postType
                R.div className: "fields #{@state.postType}",
                    switch @state.postType
                        when "link"
                            [
                                R.div null,
                                    R.input type:"text", placeholder: "Title"
                                R.div null,
                                    R.input type:"text", placeholder: "URL"
                                R.div null,
                                    R.textarea placeholder: "Description"
                            ]
                        when "status"
                            R.textarea placeholder: "What's up?"
                        when "photo"
                            R.input type: "file", ref: "photo"
                    R.div className: "buttons",
                        R.button null, "Post"
                        R.a onClick: @setPostType.bind(this, undefined), "Nevermind"


exports.Post = Post = React.createClass
    render: ->
        {article} = React.DOM
        article null,
            @props.post.content


exports.PostList = PostList = React.createClass
    data: [
        {
            type: "status",
            content: "Foo bar"
        },
        {
            type: "link",
            url: "http://google.at",
            title: "This is a hot link"
        }
    ]
    render: ->
        R = React.DOM

        R.ul null,
            for post in @data
                R.li className: "post post-#{post.type}", Post({post})


exports.CustomPropertyList = React.createClass
    getInitialState: ->
        if @props.properties
            console.log(@props.properties)
            props = []
            for prop, value of @props.properties
                props.push({property: prop, value: value})
            console.log(props)
            return properties: props
        else
            properties: []
    render: ->
        R = React.DOM
        R.div null,
            R.ul null,
                for prop, i in @state.properties
                    R.li key: i,
                        R.input placeholder: "Property", className: "property", name: "post[custom][#{ i }][property]", type: "text", value: prop.property, onChange: (ev) => @changePropertyName(ev, i)
                        R.input placeholder: "Value", className: "value", name: "post[custom][#{ i }][value]", type: "text", value: prop.value, onChange: (ev) => @changePropertyValue(ev, i)
            R.a href: "#", onClick: @handleAddProperty, "+ Add property"
    handleAddProperty: ->
        @setState({properties: @state.properties.concat([{property: "", value: ""}])})
    changePropertyName: (event, index) ->
        console.log(index)
        props = @state.properties
        props[index].property = event.target.value
        @setState(properties: props)
    changePropertyValue: (event, index) ->
        props = @state.properties
        props[index].value = event.target.value
        @setState(properties: props)




## Notes on cross linking sequences

### LinkOut

For a sequence such as GI:157058307 we can use elink to discover what else links to it:

http://eutils.ncbi.nlm.nih.gov/entrez/eutils/elink.fcgi?dbfrom=nucleotide&id=157058307&cmd=llinks&retmode=xml

```xml
<eLinkResult>
<LinkSet>
<DbFrom>nuccore</DbFrom>
<IdUrlList>
<IdUrlSet>
<Id>157058307</Id>
<ObjUrl>
<Url>http://arctos.database.museum/guid/UAM:Mamm:86892</Url>
<LinkName>UAM:Mamm:86892</LinkName>
<SubjectType>taxonomy/phylogenetic</SubjectType>
<Category>Molecular Biology Databases</Category>
<Attribute>free resource</Attribute>
<Provider>
<Name>Arctos Specimen Database</Name>
<NameAbbr>Arctos</NameAbbr>
<Id>3849</Id>
<Url LNG="EN">http://arctos.database.museum/home.cfm</Url>
</Provider>
</ObjUrl>
<ObjUrl>
<Url>
http://www.boldsystems.org/connectivity/specimenlookup.php?processid=ABUAM088-07
</Url>
<LinkName>BOLD Link [ABUAM088-07]</LinkName>
<SubjectType>taxonomy/phylogenetic</SubjectType>
<Category>Molecular Biology Databases</Category>
<Attribute>free resource</Attribute>
<Provider>
<Name>Barcodes of Life</Name>
<NameAbbr>BoLD</NameAbbr>
<Id>5067</Id>
<Url LNG="EN">http://www.barcodinglife.com/</Url>
</Provider>
</ObjUrl>
</IdUrlSet>
</IdUrlList>
</LinkSet>
</eLinkResult>
```

http://arctos.database.museum/guid/UAM:Mamm:86892 is URL to Arctos record for this specimen.








